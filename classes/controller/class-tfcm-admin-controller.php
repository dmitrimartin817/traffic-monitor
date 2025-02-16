<?php
/**
 * File: /classes/controller/class-tfcm-admin-controller.php
 *
 * This file defines the TFCM_Admin_Controller class which handles all admin-related
 * functionality for the Traffic Monitor plugin, including menu registration, screen options,
 * and rendering the log page.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class TFCM_Admin_Controller
 *
 * Manages the Traffic Monitor admin interface including menus, screen options, and page rendering.
 */
class TFCM_Admin_Controller {
	/**
	 * Registers all necessary admin-related hooks.
	 *
	 * Hooks include admin menu registration, screen options, and column management.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
		add_filter( 'default_hidden_columns', array( __CLASS__, 'set_default_hidden_columns' ), 10, 2 );
		add_action( 'set-screen-option', array( __CLASS__, 'save_screen_options' ), 10, 3 );
		add_filter( 'hidden_columns', array( __CLASS__, 'get_hidden_columns' ), 10, 2 );
	}

	/**
	 * Registers the Traffic Monitor admin menu in the dashboard.
	 *
	 * Checks user capabilities before adding the menu page and sets up the screen options callback.
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
	 * Renders the main admin page for Traffic Monitor.
	 *
	 * If a request to view details is detected, renders detailed request info; otherwise,
	 * displays the log table.
	 *
	 * @return void
	 */
	public static function render_admin_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified in render_request_details().
		if ( isset( $_GET['action'] ) && 'view_details' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) && isset( $_GET['id'] ) ) {
			self::render_request_details();
			return;
		}

		$tfcm_table = new TFCM_Log_Table();
		$tfcm_table->prepare_items();

		TFCM_View::render_admin_page( $tfcm_table );
	}

	/**
	 * Renders detailed information for a specific log entry.
	 *
	 * Validates the nonce and retrieves the log entry from the database, displaying error messages if needed.
	 *
	 * @return void
	 */
	private static function render_request_details() {
		// Verify nonce for security.
		if ( ! isset( $_GET['tfcm_details_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['tfcm_details_nonce'] ) ), 'tfcm_details_nonce' ) ) {
			TFCM_View::display_notice( 'Invalid request. Please try again.', 'error' );
			TFCM_View::display_back_button();
			return;
		}

		$log_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
		if ( 0 === $log_id ) {
			TFCM_View::display_notice( 'Invalid log entry.', 'error' );
			TFCM_View::display_back_button();
			return;
		}

		// $log = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', TFCM_REQUEST_LOG_TABLE, $log_id ), ARRAY_A );
		$log = TFCM_Database::get_single_request( $log_id );

		if ( ! $log ) {
			TFCM_View::display_notice( 'Log not found.', 'error' );
			TFCM_View::display_back_button();
			return;
		}

		TFCM_View::render_request_details( $log );
	}

	/**
	 * Configures and handles screen options for the admin page.
	 *
	 * Sets up the "elements per page" option and initializes the log table instance for the current screen.
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
	 * Sets the default hidden columns for the log table.
	 *
	 * Applies to the Traffic Monitor admin page, specifying which columns are hidden by default.
	 *
	 * @param array  $hidden The current default hidden columns.
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
	 * Saves the custom screen option for the number of elements displayed per page.
	 *
	 * @param mixed  $status The current status of the option.
	 * @param string $option The option name.
	 * @param mixed  $value  The new value to be saved.
	 * @return mixed The updated value or original status.
	 */
	public static function save_screen_options( $status, $option, $value ) {
		if ( 'tfcm_elements_per_page' === $option ) {
			return (int) $value;
		}
		return $status;
	}

	/**
	 * Retrieves the hidden columns for the log table based on user preferences.
	 *
	 * @param array     $hidden Existing hidden columns.
	 * @param WP_Screen $screen The current screen object.
	 * @return array Updated hidden columns array.
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
}
