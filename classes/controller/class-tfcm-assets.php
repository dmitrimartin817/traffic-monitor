<?php
/**
 * TFCM_Assets class file.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles script and style enqueueing for Traffic Monitor.
 */
class TFCM_Assets {
	/**
	 * Registers asset hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_client_scripts' ) );
	}

	/**
	 * Enqueues admin scripts and styles.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public static function enqueue_admin_scripts( $hook ) {
		global $tfcm_admin_page;

		if ( $hook !== $tfcm_admin_page ) {
			return;
		}

		wp_enqueue_script(
			'tfcm-admin-notices',
			plugin_dir_url( TFCM_PLUGIN_FILE ) . 'assets/js/tfcm-admin-script.js',
			array( 'jquery' ),
			TRAFFIC_MONITOR_VERSION,
			true
		);

		// this is used in tfcm-admin-script.js file.
		wp_localize_script(
			'tfcm-admin-notices',
			'tfcmAjax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'tfcm_ajax_nonce' ),
			)
		);

		wp_enqueue_style(
			'tfcm-admin-styles',
			plugin_dir_url( TFCM_PLUGIN_FILE ) . 'assets/css/tfcm-admin-style.css',
			array(),
			TRAFFIC_MONITOR_VERSION
		);
	}

	/**
	 * Enqueues frontend scripts.
	 *
	 * @return void
	 */
	public static function enqueue_client_scripts() {
		wp_enqueue_script(
			'tfcm-client-script',
			plugin_dir_url( TFCM_PLUGIN_FILE ) . 'assets/js/tfcm-client-script.js',
			array( 'jquery' ),
			TRAFFIC_MONITOR_VERSION,
			true
		);

		wp_localize_script(
			'tfcm-client-script',
			'tfcmClientAjax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'tfcm_client_ajax_nonce' ),
			)
		);
	}
}
