<?php
/**
 * All Tickets screen.
 *
 * @since  TBD
 *
 * @version TBD
 *
 * @var \Tribe__Template  $this           Current template object.
 * @var bool              $tickets_exist  Whether tickets exist.
 */

if ( $tickets_exist ) {
	return;
}

?>
<h1>
	<?php esc_html_e( 'All Tickets', 'event-tickets' ); ?>
</h1>
<div class="tec-tickets-admin-all-tickets-no-tickets-wrap">
	<div class="tec-tickets-admin-all-tickets-no-tickets-inner-wrap">
		<div>
			<img
				class="tec-tickets-admin-all-tickets-no-tickets-icon"
				src="<?php echo esc_url( tribe_resource_url( 'icons/no-tickets.svg', false, null, \Tribe__Tickets__Main::instance() ) ); ?>"
				alt="No tickets icon"
				/>
		</div>
		<div class="tec-tickets-admin-all-tickets-no-tickets-heading">
			<?php esc_html_e( 'Nothing here yet.', 'event-tickets' ); ?>
		</div>
		<div class="tec-tickets-admin-all-tickets-no-tickets-content">
			<?php
			$edit_posts_link = sprintf(
				'<a href="%s" target="_blank" rel="nofollow noopener">%s</a>',
				esc_url( $edit_posts_url ),
				esc_html__( 'events and other posts', 'event-tickets' )
			);
			$kb_link = sprintf(
				'<a href="%s" target="_blank" rel="nofollow noopener">%s</a>',
				esc_url( 'https://evnt.is/all-tickets-admin' ),
				esc_html__( 'knowledgebase', 'event-tickets' )
			);
			printf(
				// Translators: %1$s is a link to the events and other posts page, %2$s is a link to the knowledgebase.
				esc_html__( 'Create new tickets within %1$s. Once you have tickets, they\'ll all show up in one place here. Learn more at the %2$s.', 'event-tickets' ),
				wp_kses_post( $edit_posts_link ),
				wp_kses_post( $kb_link )
			);
			?>
		</div>
	</div>
</div>
