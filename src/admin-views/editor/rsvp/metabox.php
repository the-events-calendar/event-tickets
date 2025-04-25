<?php
/**
 * @var Tribe__Tickets__Admin__Views $admin_views
 * @var WP_Post                      $post
 * @var int                          $post_id
 */

?>

<div class="tribe-tickets-editor-blocker">
	<span class="spinner"></span>
</div>

<div id="tec_tickets_rsvp_metabox" class="eventtable" aria-live="polite">
	<?php wp_nonce_field( 'tec-tickets-rsvp-meta-box', 'tec-tickets-rsvp-post-settings' ); ?>
	I am RSVP
</div>
