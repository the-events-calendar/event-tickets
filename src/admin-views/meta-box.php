<?php
/**
 * @var WP_Post $post
 * @var bool $show_global_stock
 * @var Tribe__Tickets__Global_Stock $global_stock
 */

// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
$post_id = get_the_ID();

$attendees_url = tribe( 'tickets.attendees' )->get_report_link( get_post( $post_id ) );
?>

<div class="tribe-tickets-editor-blocker">
	<span class="spinner"></span>
</div>

<div id="event_tickets" class="eventtable" aria-live="polite">
	<?php
	wp_nonce_field( 'tribe-tickets-meta-box', 'tribe-tickets-post-settings' );

	// the main panel
	require_once( 'base_admin_panel.php' );

	// the add/edit panel
	require_once( 'edit_admin_panel.php' );

	// the settings panel
	require_once( 'settings_admin_panel.php' );
	?>
</div>
