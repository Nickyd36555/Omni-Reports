<?php
/**
 * CSV Exporter.
 *
 * @package OmniReports
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Omni_Reports_Exporter
 */
class Omni_Reports_Exporter {

	/**
	 * Output CSV headers and rows, then exit.
	 *
	 * @param string  $filename  Filename without extension.
	 * @param array   $rows      Array of associative arrays or stdClass objects.
	 */
	public static function output_csv( $filename, array $rows ) {
		if ( empty( $rows ) ) {
			wp_die( esc_html__( 'No data to export.', 'omni-reports' ) );
		}

		$filename = sanitize_file_name( $filename ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// BOM for Excel UTF-8 compatibility.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Header row.
		$first = (array) $rows[0];
		fputcsv( $output, array_keys( $first ) );

		foreach ( $rows as $row ) {
			fputcsv( $output, (array) $row );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Build a flat array suitable for CSV from nested report data.
	 *
	 * @param mixed  $data
	 * @param string $key  Subkey if $data is an associative array.
	 * @return array
	 */
	public static function flatten( $data, $key = '' ) {
		if ( is_array( $data ) && $key && isset( $data[ $key ] ) ) {
			return (array) $data[ $key ];
		}
		if ( is_array( $data ) ) {
			return $data;
		}
		return [];
	}
}
