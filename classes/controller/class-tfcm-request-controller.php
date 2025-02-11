<?php
/**
 * TFCM_Request_Controller class file.
 *
 * @package TrafficMonitor
 */

// Disabled lint rules.
// phpcs:disable Squiz.Commenting.VariableComment.Missing
// phpcs:disable Squiz.Commenting.InlineComment.InvalidEndChar
// phpcs:disable Squiz.PHP.CommentedOutCode.Found

defined( 'ABSPATH' ) || exit;

/**
 * Determines which requests should be logged and delegates them to the logging class.
 *
 * @package TrafficMonitor
 */
class TFCM_Request_Controller {
	/**
	 * Handles incoming HTTP requests and determines whether they should be logged.
	 *
	 * @return void
	 */
	public static function handle_request() {
		// Detect request type and instantiate the correct class
		$request_type = TFCM_Request_Abstract::get_request_type();

		switch ( $request_type ) {
			case 'ADMIN':
				return; // ignore request
			case 'AJAX':
				$request = new TFCM_Request_Ajax();
				break;
			case 'API':
				return; // ignore request
			case 'CRON':
				return; // ignore request
			case 'HTTP':
				$request = new TFCM_Request_Http();
				break;
			case 'XML-RPC':
				return; // ignore request
			case 'UNKNOWN':
				return; // ignore request
			default:
				return; // ignore other requests
		}

		// Exclude non-HTML requests and static files
		// error_log( '$request_type = ' . $request_type . ' on ' . __LINE__ . ' of ' . basename( __FILE__ ) . ' file of Traffic Monitor plugin' );
		// error_log( '$request->accept = ' . $request->accept . ' on ' . __LINE__ . ' of ' . basename( __FILE__ ) . ' file of Traffic Monitor plugin' );
		// error_log( '$request->request_url = ' . $request->request_url . ' on ' . __LINE__ . ' of ' . basename( __FILE__ ) . ' file of Traffic Monitor plugin' );
		if (
			stripos( $request->accept ?? '', 'text/html' ) === false ||
			preg_match( '/\.(css|js|jpg|jpeg|png|gif|svg|woff|woff2|ttf|ico|map)$/i', $request->request_url ?? '' ) ||
			stripos( $request->request_url ?? '', '/wp-json/' ) !== false
		) {
			return;
		}

		// Log request through the log controller
		$log_controller = new TFCM_Log_Controller( $request );
		$log_controller->process_request();
	}
}
