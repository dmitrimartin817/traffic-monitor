<?php
/**
 * Traffic Monitor - Caching Functions
 *
 * @package TrafficMonitor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles an AJAX request to check the cache status using WordPress Site Health.
 *
 * @return void Outputs JSON response with cache detection result.
 */
function tfcm_ajax_get_cache_status() {
	check_ajax_referer( 'tfcm_ajax_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized access.' ), 403 );
	}

	$user_id           = get_current_user_id();
	$already_signed_up = get_user_meta( $user_id, 'tfcm_already_signed_up', true );

	if ( $already_signed_up ) {
		wp_send_json_success(
			array(
				'message'     => '',
				'show_signup' => false,
			)
		);
	}

	$now                      = time();
	$last_dismissed_timestamp = get_user_meta( $user_id, 'tfcm_cache_notice_last_dismissed', true );

	if ( ! is_numeric( $last_dismissed_timestamp ) ) {
		$parsed_time              = strtotime( $last_dismissed_timestamp );
		$last_dismissed_timestamp = $parsed_time ? $parsed_time : 0;
	}

	if ( $last_dismissed_timestamp && ( $now - $last_dismissed_timestamp ) < DAY_IN_SECONDS ) {
		wp_send_json_success(
			array(
				'message'     => '',
				'show_signup' => false,
			)
		);
	}

	$cache_notice_dismissals = get_user_meta( $user_id, 'tfcm_cache_notice_dismissals', true );
	$cache_notice_dismissals = is_numeric( $cache_notice_dismissals ) ? intval( $cache_notice_dismissals ) : 0;
	if ( $cache_notice_dismissals >= 3 ) {
		wp_send_json_success(
			array(
				'message'     => '',
				'show_signup' => false,
			)
		);
	}

	$cache_status = tfcm_get_cache_status_via_site_health();

	wp_send_json_success( $cache_status );
}

/**
 * Handles AJAX request when the user dismisses the cache notice.
 *
 * @return void
 */
function tfcm_ajax_dismiss_cache_notice() {
	check_ajax_referer( 'tfcm_ajax_nonce', 'nonce' );

	$user_id = get_current_user_id();
	$now     = time();

	$cache_notice_dismissals = get_user_meta( $user_id, 'tfcm_cache_notice_dismissals', true );
	$cache_notice_dismissals = is_numeric( $cache_notice_dismissals ) ? intval( $cache_notice_dismissals ) : 0;

	update_user_meta( $user_id, 'tfcm_cache_notice_last_dismissed', $now );
	update_user_meta( $user_id, 'tfcm_cache_notice_dismissals', $cache_notice_dismissals + 1 );

	wp_send_json_success( array( 'message' => 'Notice dismissed successfully.' ) );
}

/**
 * Uses WordPress Site Health API to determine if page caching is enabled.
 *
 * @return array Cache status and details.
 */
function tfcm_get_cache_status_via_site_health() {
	if ( ! class_exists( 'WP_Site_Health' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
	}

	$site_health = WP_Site_Health::get_instance();

	if ( ! method_exists( $site_health, 'get_test_page_cache' ) ) {
		return array(
			'message'     => 'Page cache detection is not available in this WordPress version.',
			'show_signup' => false,
		);
	}

	$cache_result = $site_health->get_test_page_cache();

	if ( empty( $cache_result ) || ! is_array( $cache_result ) || ! isset( $cache_result['status'] ) ) {
		return array(
			'message'     => 'Could not determine caching status. Try disabling page caching in your hosting settings or caching plugin.',
			'show_signup' => false,
		);
	}

	return array(
		'message'     => 'Warning: <a href="' . esc_url( admin_url( 'admin.php?page=traffic-monitor' ) ) . '" id="tfcm-open-troubleshooting">Caching detected</a>. This free plugin version doesn’t log cached pages. Sign up to be notified when our Pro version is released.',
		'show_signup' => ( 'recommended' !== $cache_result['status'] ),
	);
}

/**
 * Marks a user as signed up when they submit the email form.
 *
 * @return void
 */
function tfcm_mark_user_signed_up() {
	check_ajax_referer( 'tfcm_ajax_nonce', 'nonce' );

	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	if ( empty( $email ) || ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'Invalid email address.' ) );
	}

	$user_id = get_current_user_id();
	update_user_meta( $user_id, 'tfcm_already_signed_up', true );

	wp_send_json_success( array( 'message' => 'Thank you for signing up!' ) );
}
