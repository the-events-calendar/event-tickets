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
<div style="height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center">
	<div>
		<img class="tribe-events-admin-title__logo" src="https://beneficent-gentoo-42009c.instawp.xyz/wp-content/plugins/the-events-calendar/common/src/resources/images/logo/event-tickets-plus.svg" alt="Event Tickets logo">
	</div>
	<div>
		Nothing here yet.
	</div>
	<div>
		Create new tickets within <a href="javascript:void(0)">events and other posts</a>.
		Once you have tickets, they'll all show up in one place here.
		Learn more at the <a href="javascript:void(0)">knowledgebase</a>
	</div>
</div>
