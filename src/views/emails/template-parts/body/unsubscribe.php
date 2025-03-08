<?php
/**
 * Event Tickets Plus Emails: Main template > Body > Unsubscribe.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/unsubscribe.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.20.0
 *
 * @since 5.20.0
 *
 * @var Subscriber $subscriber The post object with properties.
 */

use TEC\Tickets_Plus\Waitlist\Subscriber;

if ( empty( $subscriber ) ) {
	return;
}

if ( ! $subscriber instanceof Subscriber ) {
	return;
}

?>
<tr>
	<td class="tec-tickets__email-table-content-post-title-container">
		<p>
			<?php
			printf(
				// translators: 1 is opening a tag and 2 is closing a tag.
				esc_html_x( 'To unsubscribe %1$sclick here%2$s', 'Unsubscribe link in waitlist emails', 'event-tickets' ),
				'<a href="' . esc_url( $subscriber->get_unsubscribe_url() ) . '" target="_blank" rel="noopener noreferrer">',
				'</a>'
			);
			?>
		</p>
	</td>
</tr>
