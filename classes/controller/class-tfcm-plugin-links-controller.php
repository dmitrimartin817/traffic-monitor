<?php
/**
 * TFCM_Plugin_Links_Controller class file.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin action links and meta links in the WordPress Plugins page.
 *
 * @package TrafficMonitor
 */
class TFCM_Plugin_Links_Controller {

	/**
	 * Registers hooks for plugin action and meta links.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'plugin_action_links_' . plugin_basename( TFCM_PLUGIN_FILE ), array( self::class, 'add_action_links' ) );
		add_filter( 'plugin_row_meta', array( self::class, 'add_meta_links' ), 10, 2 );
	}

	/**
	 * Adds action links (e.g., "Settings") to the plugin row in the Plugins page.
	 *
	 * @param array $links An array of existing action links.
	 * @return array Modified array of action links.
	 */
	public static function add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=traffic-monitor' ) ) . '">Settings</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Adds meta links (e.g., "Pro Version" and "Rate this plugin") to the plugin row in the Plugins page.
	 *
	 * @param array  $links An array of existing meta links.
	 * @param string $file  The current plugin file.
	 * @return array Modified array of meta links.
	 */
	public static function add_meta_links( $links, $file ) {
		if ( plugin_basename( TFCM_PLUGIN_FILE ) === $file ) {
			$links[] = '<a href="https://viablepress.com/trafficmonitorpro" target="_blank">Pro Version</a>';
			$links[] = '<a href="https://wordpress.org/support/plugin/traffic-monitor/reviews/#new-post" target="_blank">Rate this plugin</a>';
		}
		return $links;
	}
}
