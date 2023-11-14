<?php
/**
 * Conditional Warnings for the Editor.
 */

namespace Tribe\Tickets\Editor;

/**
 * Warnings handling class.
 *
 * @since 5.0.4
 */
class Warnings {

	/**
	 * Hooks actions for showing warnings
	 *
	 * @since 5.0.4
	 */
	public function hook() {
		add_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'show_recurring_event_warning_message' ] );
		add_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'add_commerce_provider_warning' ] );
	}

	/**
	 * Show the Recurring Event warning message.
	 *
	 * @since 5.6.7 Remove rendering the warning message for CE.
	 * @since 5.6.4 Remove dependency on `#tribe-recurrence-active`.
	 * @since 5.6.2 Added 'recurring_event_warning' as an $additionalClasses.
	 * @since 5.0.4
	 *
	 * @param int $post_id Post ID.
	 */
	public function show_recurring_event_warning_message( $post_id ) {
		if ( ! class_exists( 'Tribe__Events__Pro__Main' ) || ! class_exists( 'Tribe__Events__Main' ) ) {
			return;
		}

		if ( ! function_exists( 'tribe_is_recurring_event' ) ) {
			return;
		}

		if ( \Tribe__Events__Main::POSTTYPE != get_post_type( $post_id ) && ! tribe_is_frontend() ) {
			return;
		}

		if ( class_exists( '\TEC\Events\Custom_Tables\V1\Migration\State' ) ) {
			$migrated = tribe( \TEC\Events\Custom_Tables\V1\Migration\State::class )->is_migrated();

			if ( ! $migrated ) {
				return;
			}
		}

		$this->render_notice( $this->get_recurring_event_warning_message(), 'info', null, null, [ 'recurring_event_warning' ] );
	}

	/**
	 * Add Provider missing warning for tickets.
	 *
	 * @since 5.6.2 Added 'provider_warning' as an $additionalClasses.
	 * @since 5.0.4
	 */
	public function add_commerce_provider_warning() {
		$available_modules = array_diff_key( \Tribe__Tickets__Tickets::modules(), [ 'Tribe__Tickets__RSVP' => true ] );

		if ( count( $available_modules ) > 0 ) {
			return;
		}

		$this->render_notice( $this->get_commerce_provider_missing_warning_message(), 'info', '', '', [ 'provider-warning' ] );
	}

	/**
	 * Get the Commerce Provider missing warning message.
	 *
	 * @since 5.0.4
	 *
	 * @return string The Commerce Provider missing message.
	 */
	public function get_commerce_provider_missing_warning_message() {
		$kb_url = 'https://evnt.is/1ao5';

		/* translators: %1$s: URL for help link, %2$s: Label for help link. */
		$link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $kb_url ),
			esc_html_x( 'Learn More', 'Helper link in Ticket Editor', 'event-tickets' )
		);

		return wp_kses_post(
			sprintf(
				/* translators: %1$s: link to help article. */
				__( 'There is no payment gateway configured. To create tickets, you\'ll need to enable and configure an ecommerce solution. %1$s', 'event-tickets' ),
				$link
			)
		);
	}

	/**
	 * Get the Recurring Event warning message.
	 *
	 * @since 5.0.4
	 *
	 * @return string The Recurring Event warning message.
	 */
	public function get_recurring_event_warning_message() {
		return sprintf(
				__( 'Tickets and RSVPs are not yet supported on recurring events. %1$s%2$s Read about our plans for future features %3$s', 'event-tickets' ),
				'<br />',
				'<a className="tribe-editor__not-supported-message-link"
					href="https://evnt.is/1b7a"
					target="_blank"
					rel="noopener noreferrer" >',
				'</a>'
		);
	}

	/**
	 * Render the notice block.
	 *
	 * @since 5.6.2 added the `$additionalClasses` attribute to allow customizing the notice.
	 * @since 5.0.4
	 *
	 * @param string $message           The message to show.
	 * @param string $type              Type of message. Default is 'info'.
	 * @param string $depends_on        Dependency selector. Default is empty.
	 * @param string $condition         Dependency condition like 'checked' | 'not-checked' | 'numeric'. Default is empty.
	 * @param array  $additionalClasses Additional CSS classes to add to the notice block. Default is an empty array.
	 */
	public function render_notice( $message, $type = 'info', $depends_on = '', $condition = '', $additionalClasses = [] ) {
		$icon           = 'dashicons-' . $type;
		$has_dependency = empty( $depends_on ) ? '' : 'tribe-dependent';
		$condition_attr = empty( $condition ) ? '' : 'data-condition-is-' . $condition;

		$classes = [
			'ticket-editor-notice',
			$type,
			$has_dependency
		];
		$classes = array_merge( $classes, $additionalClasses );

		?>
		<div <?php tribe_classes( $classes ); ?>
			<?php if ( $depends_on ) { ?>
				data-depends="<?php echo esc_attr( $depends_on ); ?>"
			<?php } ?>
			<?php echo esc_attr( $condition_attr ); ?>
		>
			<span class="dashicons <?php echo esc_attr( $icon ); ?>"></span>
			<span class="message"><?php echo wp_kses_post( $message ); ?></span>
		</div>
		<?php
	}

}
