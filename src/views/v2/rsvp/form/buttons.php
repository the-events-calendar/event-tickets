<?php
/**
 * Block: RSVP
 * Form fields
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/form/buttons.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.12.3
 *
 * @version 4.12.3
 */

?>
<div class="tribe-tickets__rsvp-form-buttons">
	<?php $this->template( 'v2/rsvp/form/fields/cancel', [ 'rsvp' => $rsvp, 'going' => $going ] ); ?>
	<?php $this->template( 'v2/rsvp/form/fields/submit', [ 'rsvp' => $rsvp, 'going' => $going ] ); ?>
</div>
