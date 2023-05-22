<?php
/**
 * Event Tickets Emails: Main template > Body > Additional Content.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/additional-content.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.11
 *
 * @since 5.5.11
 *
 * @var \Tribe__Template $this               Current template object.
 * @var \WP_Post         $order              The order object.
 * @var bool             $is_tec_active      Whether `The Events Calendar` is active or not.
 * @var string           $additional_content The additional content to be added to the email.
 * @var WP_Post|null     $event              The event post object with properties added by the `tribe_get_event` function.
 *
 * @see tribe_get_event() For the format of the event object.
 */

if ( empty( $additional_content ) ) {
	return;
}
?>
<tr>
	<td class="tec-tickets__email-table-content-additional-content-container">
		<?php echo wp_kses_post( $additional_content ); ?>
	</td>
</tr>
