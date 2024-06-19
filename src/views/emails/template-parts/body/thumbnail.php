<?php
/**
 * Event Tickets Emails: Main template > Body > Thumbnail.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/thumbnail.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.8.4
 *
 * @since 5.8.4
 *
 * @var Tribe__Template                                      $this              Current template object.
 * @var \TEC\Tickets\Emails\Email_Abstract                   $email             The email object.
 * @var bool                                                 $preview           Whether the email is in preview mode or not.
 * @var bool                                                 $is_tec_active     Whether `The Events Calendar` is active or not.
 * @var string                                               $footer_content    HTML of footer content.
 * @var string                                               $header_text_color Header text color.
 * @var WP_Post|null                                         $event             The event post object with properties added by the `tribe_get_event` function.
 * @var WP_Post|null                                         $order             The order object.
 * @var array{url: string, alt: string, title: string }|null $thumbnail         The event thumbnail.
 *
 * @see tribe_get_event() For the format of the event object.
 */

if ( ! isset( $thumbnail['url'], $thumbnail['alt'], $thumbnail['title'] ) ) {
	return;
}
?>

<tr>
	<td style="padding:0" class="tec-tickets__email-table-content-event-image-container">
		<img
			class="tec-tickets__email-table-content-event-image"
			style="display:block"
			src="<?php echo esc_url( $thumbnail['url'] ); ?>"
			<?php if ( ! empty( $thumbnail['alt'] ) ) : ?>
				alt="<?php echo esc_attr( $thumbnail['alt'] ); ?>"
			<?php endif; ?>
			<?php if ( ! empty( $thumbnail['title']) ) : ?>
				title="<?php echo esc_attr( $thumbnail['title'] ); ?>"
			<?php endif; ?>
		/>
	</td>
</tr>
