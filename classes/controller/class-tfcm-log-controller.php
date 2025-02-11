<?php
/**
 * TFCM_Log_Controller class file.
 *
 * @package TrafficMonitor
 */

// Disabled lint rules.
// phpcs:disable Squiz.Commenting.VariableComment.Missing
// phpcs:disable Squiz.Commenting.InlineComment.InvalidEndChar
// phpcs:disable Squiz.PHP.CommentedOutCode.Found

defined( 'ABSPATH' ) || exit;

/**
 * Processes request and logs it into the database.
 *
 * @package TrafficMonitor
 */
class TFCM_Log_Controller {
	private $request;

	/**
	 * TFCM_Log_Controller constructor.
	 *
	 * Initializes the logger with a request object and assigns the global `$wpdb` instance.
	 *
	 * @param mixed $request The request object containing request metadata.
	 */
	public function __construct( $request ) {
		$this->request = $request;
	}

	/**
	 * Processes the request then logs it by passing it to the database model.
	 *
	 * @return void
	 */
	public function process_request() {
		// Insert any needed processing before saving to the database.  Example: send normalized data to method for each table.
		TFCM_Database::insert_request( $this->request );
	}
}
