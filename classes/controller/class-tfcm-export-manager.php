<?php
/**
 * TFCM_Export_Manager class file.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles the creation and deletion of export files for Traffic Monitor.
 */
class TFCM_Export_Manager {
	/**
	 * Directory where exports are stored.
	 *
	 * @var string
	 */
	private static $export_dir = TFCM_PLUGIN_DIR . 'exports/';

	/**
	 * Deletes old export files.
	 *
	 * @return void
	 */
	public static function delete_old_exports() {
		$find_files = glob( self::$export_dir . 'traffic-log-*.csv' );
		$files      = $find_files ? $find_files : array();
		foreach ( $files as $file ) {
			wp_delete_file( $file );
		}
	}

	/**
	 * Generates a CSV file from provided log data.
	 *
	 * @param array  $rows       The log data.
	 * @param string $file_name  The file name.
	 * @param int    $total_rows The total number of rows to include in the export.
	 * @return void
	 */
	public static function generate_csv( $rows, $file_name, $total_rows ) {
		global $wp_filesystem;

		if ( empty( $rows ) ) {
			wp_send_json_error( array( 'message' => 'No matching records found.' ), 400 );
		}

		// Initialize WP_Filesystem.
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();

		// Ensure WP_Filesystem is available.
		if ( ! $wp_filesystem ) {
			wp_send_json_error( array( 'message' => 'File system access error.' ), 400 );
		}

		// Convert data to CSV format.
		$csv_content = implode( ',', array_keys( $rows[0] ) ) . "\n"; // Add column headers.
		foreach ( $rows as $row ) {
			$csv_content .= implode( ',', array_map( array( __CLASS__, 'escape_csv_value' ), $row ) ) . "\n";
		}

		$file_path = self::$export_dir . $file_name;
		$file_url  = plugins_url( 'exports/' . $file_name, TFCM_PLUGIN_FILE );

		// Write to file.
		if ( ! $wp_filesystem->put_contents( $file_path, $csv_content, FS_CHMOD_FILE ) ) {
			wp_send_json_error( array( 'message' => 'Failed to create the export file.' ), 400 );
		}

		wp_send_json_success( array( 'message' => 'Total records exported: ' . $total_rows . ' <a href="' . esc_url( $file_url ) . '" target="_blank" rel="noopener noreferrer">Download CSV</a>' ), 200 );
	}

	/**
	 * Escapes CSV values to prevent malformed output.
	 *
	 * @param string $value The value to escape.
	 * @return string The escaped value.
	 */
	private static function escape_csv_value( $value ) {
		return '"' . str_replace( '"', '""', $value ) . '"';
	}
}
