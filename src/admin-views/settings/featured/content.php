<?php
/**
 * Featured settings box.
 *
 * @since TBD
 *
 * @var Tribe__Template              $this              Template object.
 * @var string                       $content_template  Template used for the content section.
 */

if ( empty( $content_template ) ) {
    return;
}

?>
<div class="tec-tickets__admin-settings-featured-content">
    <?php echo $content_template; // phpcs:ignore ?>
</div>