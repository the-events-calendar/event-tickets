<?php
/**
 * Event Tickets Emails: Main template > Body > Post Title.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/post-title.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.6.0
 *
 * @since 5.6.0
 *
 * @var WP_Post $post The post object with properties.
 *
 * @see get_post() For the format of the event object.
 */

if ( empty( $post ) ) {
	return;
}
if ( empty( $post->post_title ) ) {
	return;
}
?>
<tr>
	<td class="tec-tickets__email-table-content-post-title-container">
		<h3 class="tec-tickets__email-table-content-post-title">
			<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" target="_blank" rel="noopener noreferrer">
				<?php
					echo esc_html( $post->post_title );
				?>
			</a>
		</h3>
	</td>
</tr>
