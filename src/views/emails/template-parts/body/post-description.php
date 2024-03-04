<?php
/**
 * Event Tickets Emails: Main template > Body > Event > Description.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/post-description.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version TBD
 *
 * @since 5.6.0
 * @since TBD Add the post description header.
 *
 * @var WP_Post     $post                    The post object with properties.
 * @var string|null $post_description_header The post description header.
 *
 * @see get_post() For the format of the event object.
 */

if ( empty( $post ) ) {
	return;
}
if ( empty( $post->post_excerpt ) ) {
	return;
}

?>
<tr>
	<td class="tec-tickets__email-table-content-post-description-container">
		<?php if ( ! empty( $post_description_header ) ) : ?>
			<header class="tec-tickets__email-table-content__section-header tec-tickets__email-table-content__section-header--post-description">
				<?php echo esc_html( $post_description_header ); ?>
			</header>
		<?php endif; ?>
		<?php echo esc_html( $post->post_excerpt ); ?>
	</td>
</tr>
