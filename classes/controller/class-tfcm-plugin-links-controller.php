<?php
/**
 * File: /classes/controller/class-tfcm-plugin-links-controller.php
 *
 * Manages the addition of plugin action and meta links on the WordPress Plugins page.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin action links and meta links in the WordPress Plugins page.
 */
class TFCM_Plugin_Links_Controller {

	/**
	 * Registers hooks to add custom action and meta links for the plugin.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'plugin_action_links_' . plugin_basename( TFCM_PLUGIN_FILE ), array( self::class, 'add_action_links' ) );
		add_filter( 'plugin_row_meta', array( self::class, 'add_meta_links' ), 10, 2 );
	}

	/**
	 * Adds a "Settings" link to the plugin action links.
	 *
	 * @param array $links Array of existing action links.
	 * @return array Modified array with the new settings link prepended.
	 */
	public static function add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=traffic-monitor' ) ) . '">Settings</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Adds custom meta links such as "Pro Version" and "Rate this plugin" to the plugin row.
	 *
	 * @param array  $links Array of existing meta links.
	 * @param string $file  The plugin file path.
	 * @return array Modified meta links array.
	 */
	public static function add_meta_links( $links, $file ) {
		if ( plugin_basename( TFCM_PLUGIN_FILE ) === $file ) {
			$links[] = '<a href="https://viablepress.com/trafficmonitorpro" target="_blank">Pro Version</a>';
			$links[] = '<a href="https://wordpress.org/support/plugin/traffic-monitor/reviews/#new-post" target="_blank">Rate this plugin</a>';
		}
		return $links;
	}
}
