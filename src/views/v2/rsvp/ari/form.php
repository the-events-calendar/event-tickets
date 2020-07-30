<?php
/**
 * Block: RSVP
 * ARI Form
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/ari/form.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 * @var WP_Post|int $post_id The post object or ID.
 *
 * @since 4.12.3
 *
 * @version 4.12.3
 */

?>
<div class="tribe-tickets__rsvp-ar-form">

	<?php $this->template( 'v2/rsvp/ari/form/guest', [ 'rsvp' => $rsvp ] ); ?>

	<?php $this->template( 'v2/rsvp/ari/form/guest-template', [ 'rsvp' => $rsvp ] ); ?>

</div>
