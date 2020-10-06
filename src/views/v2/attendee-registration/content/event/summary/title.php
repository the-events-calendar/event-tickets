<?php
/**
 * Attendee registration
 * Content > Event > Summary > Title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/attendee-registration/content/event/summary/title.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 * @version TBD
 *
 * @var int $post_id The event/post ID.
 */

?>
<div class="tribe-tickets__registration__title">
	<header>
		<h2 class="tribe-common-h4 tribe-common-h3--min-medium">
			<a href="<?php the_permalink( $post_id ); ?>">
				<?php echo get_the_title( $post_id ); ?>
			</a>
		</h2>
	</header>
</div>
