<?php
/**
 * This template renders the summary Title
 *
 * @version 4.9
 *
 */
?>
<div class="tribe-block__tickets__registration__title">
	<header>
		<h2>
			<a href="<?php the_permalink( $event_id ); ?>">
				<?php echo get_the_title( $event_id ); ?>
			</a>
		</h2>
	</header>
</div>