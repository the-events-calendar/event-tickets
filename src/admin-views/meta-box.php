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
$header_id = get_post_meta( $post_id, $this->image_header_field, true );
$header_id = ! empty( $header_id ) ? $header_id : '';
$header_img = '';
$header_filename = '';
if ( ! empty( $header_id ) ) {
	$header_img = wp_get_attachment_image( $header_id, 'full' );
	$header_filename = basename ( get_attached_file( $header_id ) );
}

$modules = Tribe__Tickets__Tickets::modules();

$attendees_url = Tribe__Tickets__Tickets_Handler::instance()->get_attendee_report_link( get_post( $post_id ) );
?>

<div id="event_tickets" class="eventtable"  aria-live="polite">
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
