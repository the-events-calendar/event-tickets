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
 * @version 5.8.4
 *
 * @since 5.6.0
 * @since 5.8.4 Add the post description header, add the `$show_post_description` flag and the `$post_description` variable.
 *
 * @var WP_Post     $post                    The post object with properties.
 * @var string|null $post_description_header The post description header.
 * @var bool        $show_post_description   Whether to show the post description or not.
 * @var string|null $post_description        The email specific post description. This will override the post excerpt,
 *                                           if set.
 *
 * @see get_post() For the format of the event object.
 */

if (
	empty( $post )
	|| ( isset( $show_post_description ) && ! $show_post_description )
) {
	return;
}

$description = $post_description ?? $post->post_excerpt;

if ( empty( $description ) ) {
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
		<?php echo wp_kses( wpautop( $description ), 'post' ); ?>
	</td>
</tr>
