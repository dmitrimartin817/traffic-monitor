<?php
/**
 * File: /classes/controller/class-tfcm-lifecycle.php
 *
 * Handles plugin lifecycle events such as activation, deactivation, uninstallation,
 * and database updates for Traffic Monitor.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class TFCM_Lifecycle
 *
 * Manages plugin lifecycle hooks and operations.
 */
class TFCM_Lifecycle {
	/**
	 * Registers plugin lifecycle hooks for activation, deactivation, and uninstallation.
	 *
	 * Also hooks into admin initialization for database updates.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		register_activation_hook( TFCM_PLUGIN_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( TFCM_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );
		register_uninstall_hook( TFCM_PLUGIN_FILE, array( __CLASS__, 'uninstall' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_update_database' ) );
	}

	/**
	 * Handles plugin activation by updating the version and creating necessary tables.
	 *
	 * @return void
	 */
	public static function activate() {
		// Store the current plugin version in the database
		update_option( 'tfcm_plugin_version', TRAFFIC_MONITOR_VERSION );

		TFCM_Database::create_tables();
	}

	/**
	 * Checks if the database schema needs updating and applies changes.
	 *
	 * Compares the stored plugin version with the current version and updates the schema if needed.
	 *
	 * @return void
	 */
	public static function maybe_update_database() {
		$current_version = get_option( 'tfcm_plugin_version', '1.0.0' );

		if ( version_compare( $current_version, TRAFFIC_MONITOR_VERSION, '<' ) ) {

			// error_log( "Updating Traffic Monitor from $current_version to $new_version" );

			// Update version first to prevent repeated execution if something fails.
			update_option( 'tfcm_plugin_version', TRAFFIC_MONITOR_VERSION );

			TFCM_Database::create_tables();
			TFCM_Database::remove_deprecated_columns();
		}
	}

	/**
	 * Handles plugin deactivation.
	 *
	 * Currently, no actions are required upon deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Nothing to do at this time.
	}

	/**
	 * Handles plugin uninstallation by removing user meta, exports, and database tables.
	 *
	 * @return void
	 */
	public static function uninstall() {
		$users = get_users( array( 'fields' => 'ID' ) );
		foreach ( $users as $user_id ) {
			delete_user_meta( $user_id, 'tfcm_elements_per_page' );
			delete_user_meta( $user_id, 'managetoplevel_page_traffic-monitorcolumnshidden' );
		}

		// Delete exports via TFCM_Database.
		TFCM_Export_Manager::delete_old_exports();

		// Delete the database tables via TFCM_Database.
		TFCM_Database::delete_tables();
	}
}
