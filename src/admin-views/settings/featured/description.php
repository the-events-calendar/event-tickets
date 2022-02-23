<?php
/**
 * Title for featured settings box.
 *
 * @since 5.3.0
 *
 * @var Tribe__Template $this        Template object.
 * @var string          $description Featured settings description.
 */

if ( empty( $description ) ) {
	return;
}

?>
<div class="tec-tickets__admin-settings-featured-description">
	<?php echo $description; // phpcs:ignore ?>
</div>
