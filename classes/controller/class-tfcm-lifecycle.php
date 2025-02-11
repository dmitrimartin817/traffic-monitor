<?php
/**
 * TFCM_Lifecycle class file.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin lifecycle events such as activation, deactivation, and uninstallation.
 */
class TFCM_Lifecycle {
	/**
	 * Registers activation, deactivation, and uninstallation hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		register_activation_hook( TFCM_PLUGIN_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( TFCM_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );
		register_uninstall_hook( TFCM_PLUGIN_FILE, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * Handles plugin activation by ensuring database structure is correct.
	 *
	 * @return void
	 */
	public static function activate() {
		TFCM_Database::remove_deprecated_columns();
		TFCM_Database::create_tables();
	}

	/**
	 * Handles plugin deactivation (currently unused).
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Nothing to do at this time.
	}

	/**
	 * Handles plugin uninstallation.
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
