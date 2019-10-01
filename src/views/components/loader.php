<?php
/**
 * View: Loader
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/components/loader.php
 *
 * See more documentation about our views templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version TBD
 *
 */
if ( empty( $text ) ) {
	$text = 'Loading...';
}
?>
<div class="tribe-common">
	<div
		class="tribe-loader"
		role="alert"
		aria-live="assertive"
	>
		<div class="tribe-loader__spinner">
			<?php echo esc_html( $text ); ?>
		</div>
	</div>
</div>
