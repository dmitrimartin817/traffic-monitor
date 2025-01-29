<?php
/**
 * Plugin Name: Traffic Monitor
 * Plugin URI: https://github.com/dmitrimartin817/traffic-monitor
 * Description: Monitor and log HTTP traffic, including headers and User-Agent details, directly from your WordPress admin panel.
 * Version: 1.1.1
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Dmitri Martin
 * Author URI: https://www.linkedin.com/in/dmitriamartin/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: traffic-monitor
 *
 * @package TrafficMonitor
 */

// If this file is called directly, abort.
defined( 'ABSPATH' ) || die;

global $wpdb;
define( 'TFCM_TABLE_NAME', $wpdb->prefix . 'tfcm_request_log' );
define( 'TRAFFIC_MONITOR_VERSION', '1.1.1' );
define( 'TFCM_PLUGIN_FILE', __FILE__ );

require_once plugin_dir_path( __FILE__ ) . 'inc/class-tfcm-log-table.php';

// Functions in tfcm-admin-help-tabs.php file.
require_once plugin_dir_path( __FILE__ ) . 'inc/tfcm-admin-help-tabs.php';
add_action( 'admin_head', 'tfcm_add_help_tab' );

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
use donatj\UserAgent;

// Functions in tfcm-cache.php file.
require_once plugin_dir_path( __FILE__ ) . 'inc/tfcm-cache.php';
add_action( 'wp_ajax_tfcm_ajax_get_cache_status', 'tfcm_ajax_get_cache_status' );
add_action( 'wp_ajax_tfcm_dismiss_cache_notice', 'tfcm_ajax_dismiss_cache_notice' );
add_action( 'wp_ajax_tfcm_mark_user_signed_up', 'tfcm_mark_user_signed_up' );

// Functions in tfcm-plugin-lifecycle.php file.
require_once plugin_dir_path( __FILE__ ) . 'inc/tfcm-plugin-lifecycle.php';
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'tfcm_plugin_action_links' );
add_filter( 'plugin_row_meta', 'tfcm_plugin_row_meta_links', 10, 2 );
register_activation_hook( __FILE__, 'tfcm_activate_plugin' );
register_deactivation_hook( __FILE__, 'tfcm_deactivate_plugin' );
register_uninstall_hook( __FILE__, 'tfcm_uninstall_plugin' );

// Functions in this file.
add_action( 'init', 'tfcm_log_request' );
add_action( 'admin_menu', 'tfcm_add_request_log_menu' );
add_filter( 'set-screen-option', 'tfcm_set_screen_options', 10, 3 );
add_action( 'admin_enqueue_scripts', 'tfcm_enqueue_admin_scripts' );
add_action( 'wp_ajax_tfcm_bulk_action', 'tfcm_bulk_action' );

/**
 * Logs HTTP request data into the database.
 *
 * @return void
 */
function tfcm_log_request() {
	// do not log backend traffic but allow admin to test frontend page requests.
	if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		return;
	}

	$headers = function_exists( 'getallheaders' ) ? getallheaders() : tfcm_getallheaders_fallback();

	// Retrieve required values early.
	$accept      = isset( $_SERVER['HTTP_ACCEPT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) : sanitize_text_field( $headers['Accept'] ?? '' );
	$request_url = substr( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ), 0, 255 );

	// Exclusions: No need to process requests for static files or non-HTML content.
	if ( stripos( $accept, 'text/html' ) === false ||
		preg_match( '/\.(css|js|jpg|jpeg|png|gif|svg|woff|woff2|ttf|ico|map)$/i', $request_url ) ||
		stripos( $request_url, '/wp-json/' ) !== false ) {
		return;
	}

	// Process User-Agent after exclusions.
	$user_agent = isset( $headers['User-Agent'] ) ? trim( $headers['User-Agent'] ) : '';
	$ua_info    = UserAgent\parse_user_agent( $user_agent );

	// Determine User Role.
	$user_role = 'visitor';
	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		if ( ! empty( $user->roles ) ) {
			$user_role = $user->roles[0];
		}
	}

	// Prepare data for logging.
	$data = array(
		'request_time'     => current_time( 'mysql' ),
		'request_url'      => $request_url,
		'method'           => sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ),
		'referer_url'      => substr( isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : esc_url_raw( $headers['Referer'] ?? '' ), 0, 255 ),
		'ip_address'       => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
		'browser'          => sanitize_text_field( $ua_info[ UserAgent\BROWSER ] ?? '' ),
		'browser_version'  => sanitize_text_field( $ua_info[ UserAgent\BROWSER_VERSION ] ?? '' ),
		'operating_system' => sanitize_text_field( $ua_info[ UserAgent\PLATFORM ] ?? '' ),
		'device'           => strpos( $user_agent, 'Mobile' ) !== false ? 'Mobile' : 'Desktop',
		'origin'           => isset( $_SERVER['HTTP_ORIGIN'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : esc_url_raw( $headers['Origin'] ?? '' ),
		'x_real_ip'        => isset( $_SERVER['HTTP_X_REAL_IP'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) ) : sanitize_text_field( $headers['X-Real-IP'] ?? '' ),
		'x_forwarded_for'  => isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) : sanitize_text_field( $headers['X-Forwarded-For'] ?? '' ),
		'forwarded'        => isset( $_SERVER['HTTP_FORWARDED'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_FORWARDED'] ) ) : sanitize_text_field( $headers['Forwarded'] ?? '' ),
		'x_forwarded_host' => isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ) : sanitize_text_field( $headers['X-Forwarded-Host'] ?? '' ),
		'host'             => isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : sanitize_text_field( $headers['Host'] ?? '' ),
		'accept'           => $accept,
		'accept_encoding'  => isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ) : sanitize_text_field( $headers['Accept-Encoding'] ?? '' ),
		'accept_language'  => isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) : sanitize_text_field( $headers['Accept-Language'] ?? '' ),
		'content_type'     => isset( $_SERVER['CONTENT_TYPE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['CONTENT_TYPE'] ) ) : sanitize_text_field( $headers['Content-Type'] ?? '' ),
		'connection_type'  => isset( $_SERVER['HTTP_CONNECTION'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CONNECTION'] ) ) : sanitize_text_field( $headers['Connection'] ?? '' ),
		'cache_control'    => isset( $_SERVER['HTTP_CACHE_CONTROL'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CACHE_CONTROL'] ) ) : sanitize_text_field( $headers['Cache-Control'] ?? '' ),
		'user_agent'       => $user_agent,
		'user_role'        => $user_role,
		'status_code'      => http_response_code(),
	);

	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct query is required for retrieving real-time data from a custom table
	if ( false === $wpdb->insert( TFCM_TABLE_NAME, $data ) ) {
		$error = $wpdb->last_error;
		error_log( '$error is ' . $error . ' on line ' . __LINE__ . ' of ' . basename( __FILE__ ) . ' file of Traffic Monitor plugin' );
	}
}

/**
 * Fallback for getallheaders() if not available (e.g., NGINX or CLI environments).
 *
 * @return array Associative array of request headers.
 */
function tfcm_getallheaders_fallback() {
	$headers = array();
	foreach ( $_SERVER as $name => $value ) {
		if ( strpos( $name, 'HTTP_' ) === 0 ) {
			$header_name             = str_replace( '_', '-', substr( $name, 5 ) );
			$headers[ $header_name ] = $value;
		}
	}
	return $headers;
}

/**
 * Add the admin menu for viewing request logs.
 */
function tfcm_add_request_log_menu() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	global $tfcm_admin_page;
	$tfcm_admin_page = add_menu_page(
		'Traffic Monitor Settings',
		'Traffic Monitor',
		'manage_options',
		'traffic-monitor',
		'tfcm_render_request_log',
		'dashicons-list-view',
		81
	);
	add_action( "load-$tfcm_admin_page", 'tfcm_screen_options' );
}

/**
 * Renders the Traffic Monitor log page in the WordPress admin panel.
 *
 * @return void
 */
function tfcm_render_request_log() {
	if ( isset( $_GET['action'] ) && 'view_details' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) && isset( $_GET['id'] ) ) {

		// Verify nonce for security.
		if ( ! isset( $_GET['tfcm_details_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['tfcm_details_nonce'] ) ), 'tfcm_details_nonce' ) ) {
			echo '<div class="notice notice-error"><p>Invalid request. Please try again.</p></div>';
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=traffic-monitor' ) ) . '" class="button button-primary">Back to Log Table</a></p>';
			return;
		}

		$log_id = absint( wp_unslash( $_GET['id'] ) );

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is required for retrieving real-time data from a custom table, and caching is not appropriate.
		$log = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', TFCM_TABLE_NAME, $log_id ), ARRAY_A );

		if ( ! $log && $wpdb->last_error ) {
			$error = $wpdb->last_error;
			error_log( '$error is ' . $error . ' on line ' . __LINE__ . ' of ' . __LINE__ . ' of ' . basename( __FILE__ ) . ' file of Traffic Monitor plugin' );
		}

		if ( $log ) {
			echo '<div class="wrap">';
			echo '<h2>Request Details</h2>';
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=traffic-monitor' ) ) . '" class="button button-primary">Back to Log Table</a></p>';
			echo '<table class="tfcm-request-detail-table">';
			foreach ( $log as $key => $value ) {
				printf(
					'<tr><th>%s</th><td>%s</td></tr>',
					esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ),
					esc_html( $value )
				);
			}
			echo '</table>';
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=traffic-monitor' ) ) . '" class="button button-primary">Back to Log Table</a></p>';
			echo '</div>';
		} else {
			echo '<div class="notice notice-error"><p>Log not found.</p></div>';
		}

		return; // Exit to prevent the table from being displayed.
	}

	$tfcm_table = new TFCM_Log_Table();
	echo '<div class="wrap">';
	echo '<h2>Traffic Monitor</h2>';
	echo '<div id="tfcm-notices-container"></div>';
	echo '<form method="post">';
	$tfcm_table->prepare_items();
	$tfcm_table->search_box( 'search', 'search_id' );
	$tfcm_table->display();
	echo '</div></form>';
}

/**
 * Adds screen options for the Traffic Monitor admin page.
 *
 * @return void
 */
function tfcm_screen_options() {
	global $tfcm_admin_page, $tfcm_table;
	$screen = get_current_screen();

	if ( ! is_object( $screen ) || $screen->id !== $tfcm_admin_page ) {
		return;
	}

	$user_id          = get_current_user_id();
	$option_per_page  = 'tfcm_elements_per_page';
	$default_per_page = 10;
	if ( get_user_meta( $user_id, $option_per_page, true ) === '' ) {
		update_user_meta( $user_id, $option_per_page, $default_per_page );
	}
	$args = array(
		'label'   => 'Elements per page',
		'default' => $default_per_page,
		'option'  => $option_per_page,
	);
	add_screen_option( 'per_page', $args );
	add_filter( 'set-screen-option', 'tfcm_set_screen_options', 10, 3 );

	add_filter( 'default_hidden_columns', 'tfcm_default_hidden_columns', 10, 2 );

	$tfcm_table = new TFCM_Log_Table();
}

/**
 * Registers default hidden columns for the Traffic Monitor admin table.
 *
 * @param array  $hidden The default list of hidden columns.
 * @param object $screen The current screen object.
 * @return array Modified list of hidden columns.
 */
function tfcm_default_hidden_columns( $hidden, $screen ) {
	if ( 'toplevel_page_traffic-monitor' === $screen->id ) {
		$hidden = array( 'method', 'origin', 'x_real_ip', 'x_forwarded_for', 'forwarded', 'x_forwarded_host', 'host', 'accept', 'accept_encoding', 'accept_language', 'content_type', 'connection_type', 'cache_control', 'user_agent', 'user_role', 'status_code' );
	}
	return $hidden;
}
add_filter( 'default_hidden_columns', 'tfcm_default_hidden_columns', 10, 2 );

/**
 * Retrieve user-defined hidden columns for Traffic Monitor.
 *
 * @param array     $hidden Existing hidden columns.
 * @param WP_Screen $screen Current screen object.
 * @return array Updated hidden columns.
 */
function tfcm_get_hidden_columns( $hidden, $screen ) {
	if ( 'toplevel_page_traffic-monitor' === $screen->id ) {
		$user         = get_current_user_id();
		$saved_hidden = get_user_meta( $user, 'manage' . $screen->id . 'columnshidden', true );
		$all_columns  = array_keys( ( new TFCM_Log_Table() )->get_columns() );
		if ( ! is_array( $saved_hidden ) ) {
			$saved_hidden = apply_filters( 'default_hidden_columns', array(), $screen );
		}

		$final_hidden = array_intersect( $all_columns, $saved_hidden );
		return $final_hidden;
	}
	return $hidden;
}
add_filter( 'hidden_columns', 'tfcm_get_hidden_columns', 10, 2 );


/**
 * Saves the custom screen option for elements per page.
 *
 * @param mixed  $status The current option value.
 * @param string $option The name of the option being saved.
 * @param mixed  $value  The value to save for the option.
 *
 * @return mixed The saved value or the original status.
 */
function tfcm_set_screen_options( $status, $option, $value ) {
	if ( 'tfcm_elements_per_page' === $option ) {
		return (int) $value;
	}
	return $status;
}


/**
 * Enqueues admin scripts for the Traffic Monitor plugin.
 *
 * @param string $hook The current admin page hook.
 *
 * @return void
 */
function tfcm_enqueue_admin_scripts( $hook ) {
	global $tfcm_admin_page;

	if ( $hook !== $tfcm_admin_page ) {
		return;
	}

	wp_register_script(
		'tfcm-admin-notices',
		plugin_dir_url( __FILE__ ) . 'js/tfcm-script.js',
		array( 'jquery' ),
		TRAFFIC_MONITOR_VERSION,
		true
	);

	wp_enqueue_script( 'tfcm-admin-notices' );

	$user_id           = get_current_user_id();
	$already_signed_up = get_user_meta( $user_id, 'tfcm_already_signed_up', true );

	wp_localize_script(
		'tfcm-admin-notices',
		'tfcmAjax',
		array(
			'ajax_url'       => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'tfcm_ajax_nonce' ),
			'admin_email'    => wp_get_current_user()->user_email ? wp_get_current_user()->user_email : get_option( 'admin_email' ),
			'fluent_form_id' => $already_signed_up ? null : 2,
		)
	);

	wp_enqueue_style(
		'tfcm-admin-styles',
		plugin_dir_url( __FILE__ ) . 'css/tfcm-style.css',
		array(),
		TRAFFIC_MONITOR_VERSION
	);
}

/**
 * Handles AJAX bulk actions for the Traffic Monitor log.
 *
 * Accepts POST parameters to perform actions like delete or export on selected log entries.
 *
 * @return void
 */
function tfcm_bulk_action() {
	// Verify nonce.
	if ( ! check_ajax_referer( 'tfcm_ajax_nonce', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Invalid request. Please try again.' ) );
		error_log( 'tfcm_ajax_nonce nonce not verified on line ' . __LINE__ . ' of ' . basename( __FILE__ ) . ' file of Traffic Monitor plugin' );
	}

	// Restrict access to admins only.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized access.' ), 403 );
	}

	// Get the action and IDs.
	$bulk_action = isset( $_POST['bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) ) : '';
	$log_ids     = isset( $_POST['element'] ) ? wp_parse_id_list( wp_unslash( $_POST['element'] ) ) : array();

	if ( empty( $bulk_action ) ) {
		wp_send_json_error( array( 'message' => 'Please select a bulk action before clicking Apply.' ) );
	}

	if ( ( 'delete' === $bulk_action || 'export' === $bulk_action ) && empty( $log_ids ) ) {
		wp_send_json_error( array( 'message' => 'Please select the records you want to ' . $bulk_action . '.' ) );
	}

	global $wpdb;

	tfcm_delete_old_exports();

	// Generate unique filename with nonce + timestamp.
	$nonce      = wp_create_nonce( 'tfcm_csv_export' );
	$timestamp  = time();
	$file_name  = "traffic-log-{$nonce}-{$timestamp}.csv";
	$file_path  = plugin_dir_path( __FILE__ ) . 'data/' . $file_name;
	$export_url = plugin_dir_url( __FILE__ ) . 'data/' . $file_name;

	if ( 'delete' === $bulk_action ) {
		$placeholders   = implode( ', ', array_fill( 0, count( $log_ids ), '%d' ) );
		$prepare_values = array_merge( array( TFCM_TABLE_NAME ), $log_ids );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Direct query is required for immediate deletion, caching is not appropriate, and WordPress review team-approved example structure.
		$result = $wpdb->query( $wpdb->prepare( "DELETE FROM %i WHERE id IN ( $placeholders )", $prepare_values ) );

		if ( false !== $result ) {
			wp_send_json_success( array( 'message' => 'Total records deleted: ' . count( $log_ids ) ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to delete records.' ) );
		}
	} elseif ( 'export' === $bulk_action ) {
		$placeholders   = implode( ', ', array_fill( 0, count( $log_ids ), '%d' ) );
		$prepare_values = array_merge( array( TFCM_TABLE_NAME ), $log_ids );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Direct query is required for immediate deletion, caching is not appropriate, and WordPress review team-approved example structure.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE id IN ( $placeholders )", $prepare_values ), ARRAY_A );

		$total_rows = count( $log_ids );
		tfcm_generate_csv( $rows, $file_path, $export_url, $total_rows );
	} elseif ( 'delete_all' === $bulk_action ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is required for immediate database operation and caching is not applicable.
		$result = $wpdb->query( $wpdb->prepare( 'DELETE FROM %i', TFCM_TABLE_NAME ) );

		if ( false !== $result ) {
			wp_send_json_success( array( 'message' => 'All records deleted successfully. Refresh table to verify.' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to delete all records.' ) );
		}
	} elseif ( 'export_all' === $bulk_action ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is required for immediate count of all rows and caching is not applicable.
		$total_rows = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', TFCM_TABLE_NAME ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is required for fetching data from a custom table and caching is not applicable.
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i', TFCM_TABLE_NAME ), ARRAY_A );

		tfcm_generate_csv( $rows, $file_path, $export_url, $total_rows );
	}
}

/**
 * Deletes old CSV export files from the plugin's data directory.
 *
 * @return void
 */
function tfcm_delete_old_exports() {
	$data_dir = plugin_dir_path( __FILE__ ) . 'data/';
	$files    = glob( $data_dir . 'traffic-log-*.csv' ) ? glob( $data_dir . 'traffic-log-*.csv' ) : array();
	foreach ( $files as $file ) {
		wp_delete_file( $file );
	}
}

/**
 * Generates a CSV file from the provided data and saves it to the specified file path.
 *
 * @param array  $rows       The data to be written to the CSV file.
 * @param string $file_path  The file path where the CSV will be saved.
 * @param string $export_url The URL for the generated CSV file.
 * @param int    $total_rows The total number of rows to include in the export.
 *
 * @return void
 */
function tfcm_generate_csv( $rows, $file_path, $export_url, $total_rows ) {
	global $wp_filesystem;

	if ( empty( $rows ) ) {
		wp_send_json_error( array( 'message' => 'No matching records found.' ) );
	}

	// Initialize WP_Filesystem.
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	WP_Filesystem();

	// Ensure WP_Filesystem is available.
	if ( ! $wp_filesystem ) {
		wp_send_json_error( array( 'message' => 'File system access error.' ) );
	}

	// Convert data to CSV format.
	$csv_content  = '';
	$csv_content .= implode( ',', array_keys( $rows[0] ) ) . "\n"; // Add column headers.
	foreach ( $rows as $row ) {
		$csv_content .= implode( ',', array_map( 'tfcm_esc_csv_value', $row ) ) . "\n";
	}

	// Write to file.
	if ( ! $wp_filesystem->put_contents( $file_path, $csv_content, FS_CHMOD_FILE ) ) {
		wp_send_json_error( array( 'message' => 'Failed to create the export file.' ) );
	}

		wp_send_json_success( array( 'message' => 'Total records exported: ' . $total_rows . ' <a href="' . esc_url( $export_url ) . '" target="_blank" rel="noopener noreferrer">Download CSV</a>' ) );
}

/**
 * Escapes CSV values to prevent malformed output.
 *
 * @param string $value The value to escape.
 * @return string The escaped value.
 */
function tfcm_esc_csv_value( $value ) {
	return '"' . str_replace( '"', '""', $value ) . '"';
}
