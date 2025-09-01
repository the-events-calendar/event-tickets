<?php
/**
 * No Tickets screen.
 *
 * @since 5.14.0
 * @since 5.26.2 Removed the page title to avoid duplication.
 *
 * @version 5.26.2
 *
 * @var \Tribe__Template  $this           Current template object.
 * @var bool              $tickets_exist  Whether tickets exist.
 */

if ( $tickets_exist ) {
	return;
}

$edit_posts_link = sprintf(
	'<a href="%s" target="_blank" rel="nofollow noopener">%s</a>',
	esc_url( $edit_posts_url ),
	esc_html__( 'events and other posts', 'event-tickets' )
);
$kb_link         = sprintf(
	'<a href="%s" target="_blank" rel="nofollow noopener">%s</a>',
	esc_url( 'https://evnt.is/all-tickets-admin' ),
	esc_html__( 'knowledgebase', 'event-tickets' )
);
$content         = sprintf(
	// Translators: %1$s is a link to the events and other posts page, %2$s is a link to the knowledgebase.
	__( 'Create new tickets within %1$s. Once you have tickets, they\'ll all show up in one place here. Learn more at the %2$s.', 'event-tickets' ),
	$edit_posts_link,
	$kb_link
);

?>
<div class="tec-tickets-admin-tickets-no-tickets-wrap">
	<div class="tec-tickets-admin-tickets-no-tickets-inner-wrap">
		<div>
			<img
				class="tec-tickets-admin-tickets-no-tickets-icon"
				src="<?php echo esc_url( tribe_resource_url( 'icons/no-tickets.svg', false, null, \Tribe__Tickets__Main::instance() ) ); ?>"
				alt="No tickets icon"
				/>
		</div>
		<div class="tec-tickets-admin-tickets-no-tickets-heading">
			<?php esc_html_e( 'Nothing here yet.', 'event-tickets' ); ?>
		</div>
		<div class="tec-tickets-admin-tickets-no-tickets-content">
			<?php
				echo wp_kses_post( $content );
			?>
		</div>
	</div>
</div>
