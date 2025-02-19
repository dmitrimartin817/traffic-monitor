<?php
/**
 * File: /classes/view/class-tfcm-view.php
 *
 * Handles the display of admin notices, log details, and the overall Traffic Monitor admin page.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class TFCM_View
 *
 * Provides methods to render admin UI components such as notices, log detail views, and page headers.
 */
class TFCM_View {
	/**
	 * Displays an admin notice message.
	 *
	 * Outputs a dismissible notice with the specified message and type.
	 *
	 * @param string $message The notice text.
	 * @param string $type    The type of notice ('success', 'error', 'warning', or 'info'). Defaults to 'info'.
	 * @return void
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
	 * Displays a "Back to Log Table" button.
	 *
	 * Provides a button that links back to the main Traffic Monitor admin page.
	 *
	 * @return void
	 */
	public static function display_back_button() {
		printf(
			'<p><a href="%s" class="button button-primary">Back to Log Table</a></p>',
			esc_url( admin_url( 'admin.php?page=traffic-monitor' ) )
		);
	}

	/**
	 * Renders detailed view of a specific request log entry.
	 *
	 * Outputs the log details in a table format with a back button for navigation.
	 *
	 * @param array $log An associative array containing log details.
	 * @return void
	 */
	public static function render_request_details( $log ) {
		?>
		<div class="wrap">
			<h2>Request Details</h2>
			<table class="tfcm-request-detail-table">
				<?php foreach ( $log as $key => $value ) : ?>
					<tr>
						<th><?php echo esc_html( ucfirst( str_replace( '_', ' ', $key ) ) ); ?></th>
						<td>
						<?php
						if ( 'is_cached' === $key ) {
							if ( true === $value ) {
								echo 'Yes';
							} else {
								echo 'No';
							}
						} else {
							echo esc_html( $value );
						}
						?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php self::display_back_button(); ?>
		</div>
		<?php
	}

	/**
	 * Renders the main admin page with the Traffic Monitor log table.
	 *
	 * Displays the search box and log table within a form.
	 *
	 * @param TFCM_Log_Table $tfcm_table An instance of the log table.
	 * @return void
	 */
	public static function render_admin_page( $tfcm_table ) {
		?>
	<div class="wrap">
		<div id="tfcm-notices-container"></div>
		<form method="post">
			<?php
			$tfcm_table->display();
			?>
		</form>
	</div>
		<?php
	}

	/**
	 * Adds a custom header to the Traffic Monitor admin page.
	 *
	 * Outputs a header containing the logo and title when on the Traffic Monitor admin screen.
	 *
	 * @return void
	 */
	public static function add_custom_header() {
		// Ensure we're only on the Traffic Monitor admin page
		$current_screen = get_current_screen();
		if ( isset( $current_screen->id ) && 'toplevel_page_traffic-monitor' === $current_screen->id ) {

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
