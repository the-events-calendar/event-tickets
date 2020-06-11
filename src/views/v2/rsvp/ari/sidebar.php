<?php
/**
 * Block: RSVP
 * ARI Sidebar
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/ari/sidebar.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 *
 * @version TBD
 */

?>
<div class="tribe-tickets__rsvp-ar-sidebar">

	<?php $this->template( 'v2/rsvp/ari/sidebar/title', [ 'rsvp' => $rsvp ] ); ?>

	<?php $this->template( 'v2/rsvp/ari/sidebar/quantity', [ 'rsvp' => $rsvp ] ); ?>

	<?php $this->template( 'v2/rsvp/ari/sidebar/guest-list', [ 'rsvp' => $rsvp ] ); ?>

</div>

