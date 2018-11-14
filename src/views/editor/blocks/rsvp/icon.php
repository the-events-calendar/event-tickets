<?php
/**
 * This template renders the RSVP ticket icon
 *
 * @version 0.3.0-alpha
 *
 */
?>
<div class="tribe-block__rsvp__icon">
	<?php $this->template( 'editor/blocks/rsvp/icon-svg' ); ?>
	<?php esc_html_e( 'RSVP', 'events-gutenberg' ) ?>
</div>
