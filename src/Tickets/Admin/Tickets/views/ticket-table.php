<?php
/**
 * Event Tickets table tab content for the All Tickets page.
 *
 * @since TBD
 *
 * @var Page $this The current page instance.
 */

use TEC\Tickets\Admin\Tickets\List_Table;
use TEC\Tickets\Admin\Tickets\Page;

tribe_asset_enqueue_group( 'event-tickets-admin-tickets' );

/** @var Tribe__Tickets__Admin__Views $admin_views */
$admin_views = tribe( 'tickets.admin.views' );

$context = [
	'tickets_table'  => tribe( List_Table::class ),
	'page_slug'      => Page::$page_slug,
	'tickets_exist'  => Page::tickets_exist(),
	'edit_posts_url' => $this->get_link_to_edit_posts(),
];

$admin_views->template( 'admin-tickets', $context );
