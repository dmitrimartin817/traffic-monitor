<?php
/**
 * TFCM_Log_Table class file class-tfcm-view.php
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles admin notices for Traffic Monitor.
 *
 * @package TrafficMonitor
 */
class TFCM_View {
	/**
	 * Displays an admin notice.
	 *
	 * @param string $message The notice message.
	 * @param string $type    The type of notice ('success', 'error', 'warning', 'info').
	 */
	public static function display_notice( $message, $type = 'info' ) {
		$allowed_types = array( 'success', 'error', 'warning', 'info' );
		$type          = in_array( $type, $allowed_types, true ) ? $type : 'info';

		printf(
			'<div class="notice notice-%s"><p>%s</p></div>',
			esc_attr( $type ),
			esc_html( $message )
		);
	}

	/**
	 * Displays a back button to the log table.
	 */
	public static function display_back_button() {
		printf(
			'<p><a href="%s" class="button button-primary">Back to Log Table</a></p>',
			esc_url( admin_url( 'admin.php?page=traffic-monitor' ) )
		);
	}

	/**
	 * Renders request details.
	 *
	 * @param array $log The log details.
	 */
	public static function render_request_details( $log ) {
		?>
		<div class="wrap">
			<h2>Request Details</h2>
			<?php self::display_back_button(); ?>
			<table class="tfcm-request-detail-table">
				<?php foreach ( $log as $key => $value ) : ?>
					<tr>
						<th><?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?></th>
						<td><?php echo esc_html( $value ); ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php self::display_back_button(); ?>
		</div>
		<?php
	}

	/**
	 * Renders the Traffic Monitor log page.
	 *
	 * @param TFCM_Log_Table $tfcm_table The log table instance.
	 */
	public static function render_admin_page( $tfcm_table ) {
		?>
	<div class="wrap">
		<div id="tfcm-notices-container"></div>
		<form method="post">
			<?php
			$tfcm_table->search_box( 'search', 'search_id' );
			$tfcm_table->display();
			?>
		</form>
	</div>
		<?php
	}

	/**
	 * Adds a custom header below #wpadminbar and above #wpbody.
	 */
	public static function add_custom_header() {
		// Ensure we're only on the Traffic Monitor admin page
		$current_screen = get_current_screen();
		if ( isset( $current_screen->id ) && $current_screen->id === 'toplevel_page_traffic-monitor' ) {
			echo '<div class="tfcm-header">
				<div class="tfcm-logo"> 
					<a href="' . esc_url( admin_url( 'admin.php?page=traffic-monitor' ) ) . '">
						<img src="' . esc_url( plugins_url( 'assets/images/tfcm-logo-40x40.png', TFCM_PLUGIN_FILE ) ) . '" id="tfcm-logo-40x40">
					</a>
				</div>
				<h1 class="tfcm-logo-text">Traffic Monitor</h1>
			</div>';
		}
	}
}

add_action( 'in_admin_header', array( 'TFCM_View', 'add_custom_header' ) );
