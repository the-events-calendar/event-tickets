<?php
/**
 * @var WP_Post                      $post
 * @var bool                         $show_global_stock
 * @var Tribe__Tickets__Global_Stock $global_stock
 * @var array<string>                $panels    Array of rendered panel HTML
 * @var int|null                     $ticket_id Ticket ID
 */

// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$post_id = $post_id ?? get_the_ID();

/** @var Tribe__Tickets__Admin__Views $admin_views */
$admin_views = tribe( 'tickets.admin.views' );
?>

<div class="tribe-tickets-editor-blocker">
	<span class="spinner"></span>
</div>

<div id="event_tickets" class="eventtable" aria-live="polite">
	<?php
	wp_nonce_field( 'tribe-tickets-meta-box', 'tribe-tickets-post-settings' );

	foreach ( $panels as $panel ) {
		echo $panel; // phpcs:ignore StellarWP.XSS.EscapeOutput.OutputNotEscapedExpected, StellarWP.XSS.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Allows for the insertion of additional content into the ticket edit form below the form
	 * section
	 *
	 * @since 5.24.1
	 *
	 * @param int Post ID
	 * @param int Ticket ID
	 */
	do_action( 'tribe_tickets_metabox_end', $post_id, $ticket_id );
	?>
</div>
