<?php
/**
 * Conditional Warnings for the Editor.
 */

namespace Tribe\Tickets\Editor;

use Tribe__Tickets__Admin__Views;
use Tribe\Tickets\Admin\Settings as Plugin_Settings;

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
		add_action( 'tribe_events_tickets_new_ticket_warnings', [ $this, 'add_commerce_provider_warning' ] );
		add_action( 'tribe_events_tickets_new_ticket_warnings', [ $this, 'render_hidden_recurring_warning_for_ticket_meta_box' ] );
	}

	/**
	 * Show the Recurring Event warning message.
	 *
	 * @since 5.6.7 Remove rendering the warning message for CE.
	 * @since 5.6.4 Remove dependency on `#tribe-recurrence-active`.
	 * @since 5.6.2 Added 'recurring_event_warning' as an $additionalClasses.
	 * @since 5.0.4
	 * @since 5.8.0 Deprecated.
	 *
	 * @deprecated 5.8.0
	 *
	 * @param int $post_id Post ID.
	 */
	public function show_recurring_event_warning_message( $post_id ) {
		_deprecated_function( __METHOD__, '5.8.0', __CLASS__ . '::render_hidden_recurring_warning_for_ticket_meta_box' );
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

		$this->render_notice( $this->get_commerce_provider_missing_warning_message(), 'info info--background', '', '', [ 'provider-warning' ], 'lightbulb' );
	}

	/**
	 * Get the Commerce Provider missing warning message.
	 *
	 * @since 5.0.4
	 * @since 5.26.3 Changed the URL to point to the Tickets Commerce settings page.
	 *
	 * @return string The Commerce Provider missing message.
	 */
	public function get_commerce_provider_missing_warning_message() {
		$kb_url = tribe( Plugin_Settings::class )->get_url( [ 'tab' => 'payments' ] );

		/* translators: %1$s: URL for help link, %2$s: Label for help link. */
		$link = sprintf(
			'<a href="%1$s" rel="noopener noreferrer">%2$s</a>.',
			esc_url( $kb_url ),
			esc_html_x( 'Set up Tickets Commerce', 'Link to payment settings in Ticket Editor', 'event-tickets' )
		);

		$message = sprintf(
			/* Translators: %1$s: link to help article. */
			__( 'There is no payment gateway configured. To create %1$s, you\'ll need to enable and configure an ecommerce solution. %2$s', 'event-tickets' ),
			tribe_get_ticket_label_plural_lowercase( 'commerce provider missing warning' ),
			$link
		);

		/**
		 * Filter the Commerce Provider missing warning message.
		 *
		 * @since 5.8.2
		 *
		 * @param string $message The Commerce Provider missing message.
		 */
		$message = apply_filters( 'tec_tickets_commerce_provider_missing_warning_message', $message );

		return wp_kses(
			$message,
			[
				'a' => [
					'href'   => [],
					'target' => [],
					'rel'    => [],
				],
			]
		);
	}

	/**
	 * Get the Recurring Event warning message.
	 *
	 * @since 5.0.4
	 * @since 5.8.0 Removed `class` attribute, dynamize ticket and rsvp labels.
	 *
	 * @param int $post_id The Post ID.
	 */
	public function get_recurring_event_warning_message( int $post_id ): void {
		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( Tribe__Tickets__Admin__Views::class );

		$help_text_link = sprintf(
			'<a href="%1$s" target="_blank" rel="noreferrer noopener">%2$s</a>',
			esc_url( 'https://evnt.is/1b7a' ),
			esc_html( __( 'See our future planned features.', 'event-tickets' ) )
		);

		$et_message = sprintf(
		/* translators: %1$s: link to help article. */
			__( 'Standard tickets are not yet supported on recurring events. %1$s', 'event-tickets' ),
			$help_text_link
		);

		$admin_views->template(
			'editor/recurring-warning',
			[
				'post_id'  => $post_id,
				'messages' => [ 'et-warning' => $et_message ],
			],
		);
	}

	/**
	 * Render the notice block.
	 *
	 * @since 5.6.2 added the `$additionalClasses` attribute to allow customizing the notice.
	 * @since 5.0.4
	 * @since 5.8.2 Added `$dashicon` attribute to allow adding a dashicon to the notice.
	 *
	 * @param string $message           The message to show.
	 * @param string $type              Type of message. Default is 'info'.
	 * @param string $depends_on        Dependency selector. Default is empty.
	 * @param string $condition         Dependency condition like 'checked' | 'not-checked' | 'numeric'. Default is empty.
	 * @param array  $classes           Additional CSS classes to add to the notice block. Default is an empty array.
	 */
	public function render_notice( $message, $type = 'info', $depends_on = '', $condition = '', $classes = [], $dashicon = '' ) {
		$has_dependency = empty( $depends_on ) ? '' : 'tribe-dependent';
		$condition_attr = empty( $condition ) ? '' : 'data-condition-is-' . $condition;

		$base_classes = [
			'ticket-editor-notice',
			$type,
			$has_dependency,
		];
		$classes      = array_merge( $base_classes, $classes );

		?>
		<div <?php tribe_classes( $classes ); ?>
			<?php if ( $depends_on ) { ?>
				data-depends="<?php echo esc_attr( $depends_on ); ?>"
			<?php } ?>
			<?php echo esc_attr( $condition_attr ); ?>
		>
			<?php
			if ( ! empty( $dashicon ) ) {
				echo '<span class="dashicons dashicons-' . esc_attr( $dashicon ) . '"></span>';
			}
			?>
			<span class="message"><?php echo wp_kses_post( $message ); ?></span>
		</div>
		<?php
	}

	/**
	 * Render hidden recurring warning message for new post/event creation page.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The Post ID.
	 */
	public function render_hidden_recurring_warning_for_ticket_meta_box( int $post_id ): void {
		$this->get_recurring_event_warning_message( $post_id );
	}
}
