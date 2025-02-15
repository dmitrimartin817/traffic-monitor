<?php
/**
 * TFCM_Assets class file.
 *
 * @package TrafficMonitor
 */

// class-tfcm-assets.php

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
			plugins_url( 'assets/js/tfcm-admin-script.js', TFCM_PLUGIN_FILE ),
			array( 'jquery' ),
			filemtime( plugin_dir_path( TFCM_PLUGIN_FILE ) . 'assets/js/tfcm-admin-script.js' ),
			true // Load in footer
		);

		// this is used in tfcm-admin-script.js file.
		wp_localize_script(
			'tfcm-admin-notices',
			'tfcmAdminAjax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'tfcm_ajax_nonce' ),
			)
		);

		wp_enqueue_style(
			'tfcm-admin-styles',
			plugins_url( 'assets/css/tfcm-admin-style.css', TFCM_PLUGIN_FILE ),
			array(),
			filemtime( plugin_dir_path( TFCM_PLUGIN_FILE ) . 'assets/css/tfcm-admin-style.css' )
		);
	}

	/**
	 * Enqueues frontend scripts.
	 *
	 * @return void
	 */
	public static function enqueue_client_scripts() {
		global $tfcm_request_type;
		if ( 'HTTP' !== $tfcm_request_type ) {
			return;
		}

		wp_enqueue_script(
			'tfcm-client-script',
			plugins_url( 'assets/js/tfcm-client-script.js', TFCM_PLUGIN_FILE ),
			array( 'jquery' ),
			filemtime( plugin_dir_path( TFCM_PLUGIN_FILE ) . 'assets/js/tfcm-client-script.js' ),
			true // Load in footer.
		);

		// Add defer attribute.
		add_filter(
			'script_loader_tag',
			function ( $tag, $handle ) {
				if ( 'tfcm-client-script' === $handle ) {
					return str_replace( ' src', ' defer src', $tag );
				}
				return $tag;
			},
			10,
			2
		);

		global $cache_check_nonce;

		wp_localize_script(
			'tfcm-client-script',
			'tfcmClientAjax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => $cache_check_nonce,
			)
		);
		// injects like: <script type='text/javascript'> var tfcmClientAjax={'ajax_url': 'https://example.com/wp-admin/admin-ajax.php', 'nonce': '123456789abcdef'};</script>

		wp_enqueue_script( 'tfcm-client-script' );
	}
}
