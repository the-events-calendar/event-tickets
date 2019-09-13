<?php
/**
 * Block: Tickets
 * Registration Content
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/registration/content.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @version 4.9.4
 *
 */

?>
<div class="tribe-tickets__registration">

	<?php $this->template( 'blocks/tickets/registration/summary/content' ); ?>
	<?php $this->template( 'blocks/tickets/registration/attendee/content' ); ?>

</div>
