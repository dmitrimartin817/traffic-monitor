<?php
/**
 * TFCM_Request_Ajax class file.
 *
 * @package TrafficMonitor
 */

// Disabled lint rules.
// phpcs:disable Squiz.Commenting.VariableComment.Missing
// phpcs:disable Squiz.Commenting.InlineComment.InvalidEndChar
// phpcs:disable Squiz.PHP.CommentedOutCode.Found

defined( 'ABSPATH' ) || exit;

// Cconsider switching to https://developers.whatismybrowser.com/api/ .
use donatj\UserAgent\UserAgentParser;

/**
 * Extends TFCM_Request_Abstract to handle AJAX requests.
 *
 * @package TrafficMonitor
 */
class TFCM_Request_Ajax extends TFCM_Request_Abstract {
	/**
	 * Initializes a new Request instance with default values.
	 */
	public function __construct() {
		parent::__construct();
		// uncomment and replace '' if set by AJAX
		// $this->request_time set by TFCM_Request_Abstract
		// $this->request_url      = '';
		// $this->request_type set by TFCM_Request_Abstract
		// $this->method           = '';
		// $this->referer_url      = '';
		// $this->user_role set by TFCM_Request_Abstract
		// $this->ip_address       = '';
		// $this->host             = '';
		// $this->device           = '';
		// $this->operating_system = '';
		// $this->browser          = '';
		// $this->browser_version  = '';
		// $this->user_agent       = '';
		// $this->origin           = '';
		// $this->accept           = '';
		// $this->accept_encoding  = '';
		// $this->accept_language  = '';
		// $this->content_type     = '';
		// $this->connection_type  = '';
		// $this->cache_control    = '';
		// $this->status_code      = '';
	}

	/**
	 * Returns filtered data specific to AJAX requests.
	 *
	 * @return array Associative array containing AJAX request metadata.
	 */
	public function get_data() {
		$data = parent::get_data();
		// uncomment to override parent with uncommented values in construct above
		// $data['request_time']  set by TFCM_Request_Abstract
		// $data['request_url']      = $this->request_url;
		// $data['request_type']  set by TFCM_Request_Abstract
		// $data['method']           = $this->method;
		// $data['referer_url']      = $this->referer_url;
		// $data['user_role']  set by TFCM_Request_Abstract
		// $data['ip_address']       = $this->ip_address;
		// $data['host']             = $this->host;
		// $data['device']           = $this->device;
		// $data['operating_system'] = $this->operating_system;
		// $data['browser']          = $this->browser;
		// $data['browser_version']  = $this->browser_version;
		// $data['user_agent']       = $this->user_agent;
		// $data['origin']           = $this->origin;
		// $data['accept']           = $this->accept;
		// $data['accept_encoding']  = $this->accept_encoding;
		// $data['accept_language']  = $this->accept_language;
		// $data['content_type']     = $this->content_type;
		// $data['connection_type']  = $this->connection_type;
		// $data['cache_control']    = $this->cache_control;
		// $data['status_code']      = $this->status_code;
		return $data;
	}
}
