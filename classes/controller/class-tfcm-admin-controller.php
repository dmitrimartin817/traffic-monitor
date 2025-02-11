<?php
/**
 * TFCM_Admin_Controller class file.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles admin-related functionality for Traffic Monitor.
 */
class TFCM_Admin_Controller {
	/**
	 * Registers admin-related hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
		add_filter( 'default_hidden_columns', array( __CLASS__, 'set_default_hidden_columns' ), 10, 2 );
		add_action( 'set-screen-option', array( __CLASS__, 'save_screen_options' ), 10, 3 );
		add_filter( 'hidden_columns', array( __CLASS__, 'get_hidden_columns' ), 10, 2 );
		add_action( 'wp_ajax_tfcm_handle_bulk_action', array( __CLASS__, 'handle_bulk_action' ) );
	}

	/**
	 * Registers the Traffic Monitor admin menu.
	 *
	 * @return void
	 */
	public static function register_admin_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $tfcm_admin_page;
		$tfcm_admin_page = add_menu_page(
			'Traffic Monitor Settings',
			'Traffic Monitor',
			'manage_options',
			'traffic-monitor',
			array( __CLASS__, 'render_admin_page' ),
			'dashicons-list-view',
			81
		);

		add_action( "load-$tfcm_admin_page", array( __CLASS__, 'handle_screen_options' ) );
	}

	/**
	 * Renders the Traffic Monitor log page in the WordPress admin panel.
	 *
	 * @return void
	 */
	public static function render_admin_page() {
		if ( isset( $_GET['action'] ) && 'view_details' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) && isset( $_GET['id'] ) ) {
			self::render_request_details();
			return;
		}

		$tfcm_table = new TFCM_Log_Table();
		$tfcm_table->prepare_items();

		TFCM_View::render_admin_page( $tfcm_table );
	}

	/**
	 * Renders request details view.
	 *
	 * @return void
	 */
	private static function render_request_details() {
		global $wpdb;

		// Verify nonce for security.
		if ( ! isset( $_GET['tfcm_details_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['tfcm_details_nonce'] ) ), 'tfcm_details_nonce' ) ) {
			TFCM_View::display_notice( 'Invalid request. Please try again.', 'error' );
			TFCM_View::display_back_button();
			return;
		}

		$log_id = absint( wp_unslash( $_GET['id'] ) );

		$log = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', TFCM_REQUEST_LOG_TABLE, $log_id ), ARRAY_A );

		if ( ! $log ) {
			TFCM_View::display_notice( 'Log not found.', 'error' );
			TFCM_View::display_back_button();
			return;
		}

		TFCM_View::render_request_details( $log );
	}

	/**
	 * Handles screen options for the Traffic Monitor admin page.
	 *
	 * @return void
	 */
	public static function handle_screen_options() {
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

		add_screen_option(
			'per_page',
			array(
				'label'   => 'Elements per page',
				'default' => $default_per_page,
				'option'  => $option_per_page,
			)
		);

		$tfcm_table = new TFCM_Log_Table();
	}

	/**
	 * Registers default hidden columns for the Traffic Monitor admin table.
	 *
	 * @param array  $hidden The default list of hidden columns.
	 * @param object $screen The current screen object.
	 * @return array Modified list of hidden columns.
	 */
	public static function set_default_hidden_columns( $hidden, $screen ) {
		if ( 'toplevel_page_traffic-monitor' === $screen->id ) {
			$hidden = array(
				'request_type',
				'method',
				'user_role',
				'host',
				'browser_version',
				'user_agent',
				'origin',
				'accept',
				'accept_encoding',
				'accept_language',
				'content_type',
				'connection_type',
				'cache_control',
				'status_code',
			);
		}
		return $hidden;
	}

	/**
	 * Saves custom screen option for elements per page.
	 *
	 * @param mixed  $status The current option value.
	 * @param string $option The name of the option being saved.
	 * @param mixed  $value  The value to save for the option.
	 *
	 * @return mixed The saved value or the original status.
	 */
	public static function save_screen_options( $status, $option, $value ) {
		if ( 'tfcm_elements_per_page' === $option ) {
			return (int) $value;
		}
		return $status;
	}

	/**
	 * Retrieves hidden columns for the Traffic Monitor admin table.
	 *
	 * @param array     $hidden Existing hidden columns.
	 * @param WP_Screen $screen Current screen object.
	 * @return array Updated hidden columns.
	 */
	public static function get_hidden_columns( $hidden, $screen ) {
		if ( 'toplevel_page_traffic-monitor' === $screen->id ) {
			$user         = get_current_user_id();
			$saved_hidden = get_user_meta( $user, 'manage' . $screen->id . 'columnshidden', true );
			$all_columns  = array_keys( ( new TFCM_Log_Table() )->get_columns() );

			if ( ! is_array( $saved_hidden ) ) {
				$saved_hidden = apply_filters( 'default_hidden_columns', array(), $screen );
			}

			return array_intersect( $all_columns, $saved_hidden );
		}
		return $hidden;
	}

	/**
	 * Handles AJAX bulk actions for the Traffic Monitor log.
	 *
	 * @return void
	 */
	public static function handle_bulk_action() {
		global $wpdb;

		// Verify nonce.
		if ( ! check_ajax_referer( 'tfcm_ajax_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Invalid request. Please try again.' ) );
			// error_log( 'tfcm_ajax_nonce nonce not verified on line ' . __LINE__ . ' of ' . basename( __FILE__ ) . ' file of Traffic Monitor plugin' );
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

		TFCM_Export_Manager::delete_old_exports();

		// Generate unique filename with nonce + timestamp.
		$nonce     = wp_create_nonce( 'tfcm_csv_export' );
		$timestamp = time();
		$file_name = "traffic-log-{$nonce}-{$timestamp}.csv";

		if ( 'delete' === $bulk_action ) {
			$result = TFCM_Database::delete_requests( $log_ids );

			if ( false !== $result ) {
				wp_send_json_success( array( 'message' => 'Total records deleted: ' . count( $log_ids ) ) );
			} else {
				wp_send_json_error( array( 'message' => 'Failed to delete records.' ) );
			}
		} elseif ( 'export' === $bulk_action ) {
			$rows = TFCM_Database::get_requests( $log_ids );

			$total_rows = count( $log_ids );
			TFCM_Export_Manager::generate_csv( $rows, $file_name, $total_rows );
		} elseif ( 'delete_all' === $bulk_action ) {
			$result = TFCM_Database::delete_all_requests();

			if ( false !== $result ) {
				wp_send_json_success( array( 'message' => 'All records deleted successfully. Refresh table to verify.' ) );
			} else {
				wp_send_json_error( array( 'message' => 'Failed to delete all records.' ) );
			}
		} elseif ( 'export_all' === $bulk_action ) {
			$total_rows = TFCM_Database::count_requests();
			$rows       = TFCM_Database::get_all_requests();

			TFCM_Export_Manager::generate_csv( $rows, $file_name, $total_rows );
		}
	}
}
