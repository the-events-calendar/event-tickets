<?php
/**
 * Block: RSVP
 * Loader
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/loader.php
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
<?php
	ob_start();
	/**
	 * Allows filtering of extra classes used on the rsvp-block loader
	 *
	 * @since  TBD
	 *
	 * @param  array $classes The array of classes that will be filtered.
	 */
	$loader_classes = apply_filters( 'tribe_rsvp_block_loader_classes', [ 'tribe-block__rsvp__loading' ] );
	include Tribe__Tickets__Templates::get_template_hierarchy( 'components/loader.php' );
	$html = ob_get_contents();
	ob_end_clean();
	echo $html;
?>
