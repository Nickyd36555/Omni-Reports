<?php
/**
 * GitHub Auto-Updater.
 *
 * Hooks into the WordPress plugin update system and checks the GitHub
 * releases API for a newer version. When found, WordPress shows the
 * standard "Update available" notice and handles the download/install.
 *
 * @package OmniReports
 */

defined( 'ABSPATH' ) || exit;

class Omni_Reports_Updater {

	private $slug;
	private $plugin_file;
	private $github_user = 'nickyd36555';
	private $github_repo = 'omni-reports';
	private $transient_key;

	public function __construct() {
		$this->slug          = 'omni-reports/omni-reports.php';
		$this->plugin_file   = OMNI_REPORTS_FILE;
		$this->transient_key = 'omni_reports_update_check';

		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
		add_filter( 'plugins_api', [ $this, 'plugin_info' ], 20, 3 );
		add_filter( 'upgrader_post_install', [ $this, 'post_install' ], 10, 3 );
	}

	/**
	 * Fetch latest release data from GitHub (cached 12 hours).
	 */
	private function get_release() {
		$data = get_transient( $this->transient_key );
		if ( $data ) {
			return $data;
		}

		$url      = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases/latest";
		$response = wp_remote_get( $url, [
			'timeout' => 10,
			'headers' => [
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'OmniReports-Updater/' . OMNI_REPORTS_VERSION,
			],
		] );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Cache failure briefly so we don't hammer the API.
			set_transient( $this->transient_key, false, HOUR_IN_SECONDS );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $body->tag_name ) ) {
			set_transient( $this->transient_key, false, HOUR_IN_SECONDS );
			return false;
		}

		// Find the zip asset or fall back to GitHub's auto-generated zip.
		$zip_url = "https://github.com/{$this->github_user}/{$this->github_repo}/releases/download/{$body->tag_name}/omni-reports.zip";
		if ( ! empty( $body->assets ) ) {
			foreach ( $body->assets as $asset ) {
				if ( str_ends_with( $asset->name, '.zip' ) ) {
					$zip_url = $asset->browser_download_url;
					break;
				}
			}
		}

		$data = (object) [
			'version'       => ltrim( $body->tag_name, 'v' ),
			'tag'           => $body->tag_name,
			'zip_url'       => $zip_url,
			'changelog'     => $body->body ?? '',
			'published_at'  => $body->published_at ?? '',
		];

		set_transient( $this->transient_key, $data, 12 * HOUR_IN_SECONDS );
		return $data;
	}

	/**
	 * Inject update info into WordPress transient if a newer version exists.
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_release();
		if ( ! $release ) {
			return $transient;
		}

		if ( version_compare( $release->version, OMNI_REPORTS_VERSION, '>' ) ) {
			$transient->response[ $this->slug ] = (object) [
				'slug'        => 'omni-reports',
				'plugin'      => $this->slug,
				'new_version' => $release->version,
				'url'         => "https://github.com/{$this->github_user}/{$this->github_repo}",
				'package'     => $release->zip_url,
				'tested'      => '6.5',
				'requires'    => '5.6',
			];
		}

		return $transient;
	}

	/**
	 * Provide plugin info for the "View version details" modal.
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}
		if ( ! isset( $args->slug ) || 'omni-reports' !== $args->slug ) {
			return $result;
		}

		$release = $this->get_release();
		if ( ! $release ) {
			return $result;
		}

		$plugin_data = get_plugin_data( $this->plugin_file );

		return (object) [
			'name'          => $plugin_data['Name'],
			'slug'          => 'omni-reports',
			'version'       => $release->version,
			'author'        => $plugin_data['Author'],
			'homepage'      => "https://github.com/{$this->github_user}/{$this->github_repo}",
			'requires'      => '5.6',
			'tested'        => '6.5',
			'downloaded'    => 0,
			'last_updated'  => $release->published_at,
			'sections'      => [
				'description' => $plugin_data['Description'],
				'changelog'   => nl2br( esc_html( $release->changelog ) ),
			],
			'download_link' => $release->zip_url,
		];
	}

	/**
	 * After install: ensure the plugin folder is named correctly.
	 * GitHub zips extract to <repo>-<tag>/ so we rename to omni-reports/.
	 */
	public function post_install( $response, $hook_extra, $result ) {
		if ( empty( $hook_extra['plugin'] ) || $this->slug !== $hook_extra['plugin'] ) {
			return $response;
		}

		global $wp_filesystem;
		$install_dir = WP_PLUGIN_DIR . '/omni-reports';

		if ( $result['destination'] !== $install_dir ) {
			$wp_filesystem->move( $result['destination'], $install_dir, true );
			$result['destination'] = $install_dir;
		}

		// Re-activate so the plugin stays active after update.
		activate_plugin( $this->slug );

		return $result;
	}

	/**
	 * Clear the cached release data (call after pushing a new version).
	 */
	public static function clear_cache() {
		delete_transient( 'omni_reports_update_check' );
	}
}
