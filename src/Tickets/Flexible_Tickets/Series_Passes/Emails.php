<?php
/**
 * Handles the integration for emails.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use Tribe__Template as Template;
use WP_Post;

/**
 * Class Emails controller.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
 */
class Emails extends Controller {

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tribe_template_after_include:tickets/emails/template-parts/body/post-title', [ $this, 'include_series_dates_for_series_pass_email' ], 10, 3 );
		add_action( 'tribe_template_after_include:tickets/emails/template-parts/body/post-title', [ $this, 'include_series_link_for_series_pass_email' ], 11, 3 );
		add_action( 'tribe_tickets_ticket_email_after_details', [ $this, 'include_series_link_for_series_pass_for_legacy_email' ], 10, 2 );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tribe_template_after_include:tickets/emails/template-parts/body/post-title', [ $this, 'include_series_dates_for_series_pass_email' ], 10, 3 );
		remove_action( 'tribe_template_after_include:tickets/emails/template-parts/body/post-title', [ $this, 'include_series_link_for_series_pass_email' ], 11, 3 );
		remove_action( 'tribe_tickets_ticket_email_after_details', [ $this, 'include_series_link_for_series_pass_for_legacy_email' ], 10, 2 );
	}

	/**
	 * Renders the series events permalink for the legacy ticket email.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The series post ID.
	 *
	 * @return void
	 */
	public function render_series_events_permalink_for_legacy_ticket_email( int $post_id ): void {
		?>
		<a href="<?php echo esc_url( get_post_permalink( $post_id ) ); ?>" target="_blank" rel="noopener noreferrer">
			<?php echo esc_html( __( 'See all the events in this series.', 'event-tickets' ) ); ?>
		</a>
		<?php
	}

	/**
	 * Renders the series events permalink for the ticket email.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The series post ID.
	 *
	 * @return void
	 */
	public function render_series_events_permalink_for_ticket_emails( int $post_id ): void {
		?>
		<tr>
			<td class="tec-tickets__email-table-content__series-list">
				<p>
					<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( __( 'See all the events in this series.', 'event-tickets' ) ); ?>
					</a>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders the series date range for the ticket email.
	 *
	 * @since 5.8.0
	 *
	 * @param int $series_id The series ID.
	 *
	 * @return void
	 */
	public function render_series_events_date_range( int $series_id ) {
		$dates = [
			tribe_get_start_date( $series_id ),
			tribe_get_end_date( $series_id ),
		];

		$dates = array_filter( $dates );

		if ( empty( $dates ) ) {
			return;
		}
		?>
		<tr>
			<td class="tec-tickets__email-table-content__series-date">
				<?php echo esc_html( implode( ' - ', $dates ) ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Include the Series list link in the ticket emails.
	 *
	 * @since 5.8.0
	 *
	 * @param string $file       Template file.
	 * @param string $name       Template name.
	 * @param Template $template Event Tickets template object.
	 *
	 * @return void
	 */
	public function include_series_link_for_series_pass_email( $file, $name, $template ): void {
		if ( ! $template instanceof Template ) {
			return;
		}

		$context = $template->get_values();
		if ( ! isset( $context['post_id'] ) || get_post_type( $context['post_id'] ) !== Series_Post_Type::POSTTYPE ) {
			return;
		}

		$this->render_series_events_permalink_for_ticket_emails( $context['post_id'] );
	}

	/**
	 * Include the Series date range in the ticket emails.
	 *
	 * @since 5.8.0
	 *
	 * @param string $file       Template file.
	 * @param string $name       Template name.
	 * @param Template $template Event Tickets template object.
	 *
	 * @return void
	 */
	public function include_series_dates_for_series_pass_email( $file, $name, $template ): void {
		if ( ! $template instanceof Template ) {
			return;
		}

		$context = $template->get_values();
		if ( ! isset( $context['post_id'] ) || get_post_type( $context['post_id'] ) !== Series_Post_Type::POSTTYPE ) {
			return;
		}

		$this->render_series_events_date_range( $context['post_id'] );
	}

	/**
	 * Include the series link for legacy ticket emails.
	 *
	 * @since 5.8.0
	 *
	 * @param array $ticket Ticket information.
	 * @param WP_Post $event Event post object.
	 *
	 * @return void
	 */
	public function include_series_link_for_series_pass_for_legacy_email( array $ticket, WP_Post $event ):void {
		if (  get_post_type( $event ) !== Series_Post_Type::POSTTYPE ) {
			return;
		}

		$this->render_series_events_permalink_for_legacy_ticket_email( $event->ID );
	}
}