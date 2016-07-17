<?php
/**
 * @var array $post_types
 * @var array $post_choices
 */
?>
<div id="tribe-dialog-wrapper">
	<div id="heading">
		<h1> <?php esc_html_e( 'Move Ticket Type', 'event-tickets' ); ?> </h1>
	</div>

	<div id="main">
		<div id="choose-event">
			<p>
				<label for="post-type"> <?php esc_html_e( 'You can optionally focus on a specific post type:', 'event-tickets' ); ?> </label>
				<select name="post-type" id="post-type">
					<?php foreach ( $post_types as $type_slug => $type_name ): ?>
						<option name="<?php esc_attr_e( $type_slug ); ?>"> <?php esc_html_e( $type_name ); ?> </option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>
				<label for="search-terms"> <?php esc_html_e( 'You can also enter keywords to help find the target event by title or description:', 'event-tickets' ); ?> </label>
				<input type="text" name="search-terms" id="search-terms" value="" />
			</p>

			<p>
				<label for="post-choice"> <?php esc_html_e( 'Select the post you wish to move the ticket type to:', 'event-tickets' ); ?> </label>
			<div class="select-single-container">
				<?php foreach ( $post_choices as $post_id => $post_title ): ?>
					<label>
						<input type="radio" name="post-choice" value="<?php echo esc_attr( $post_id ); ?>">
						<?php echo esc_html( $post_title ); ?>
					</label>
				<?php endforeach; ?>
			</div>
			</p>
		</div>

		<div id="confirm" >
			<button class="button-primary" id="move-ticket-type" name="move-ticket-type" disabled value="move">
				<?php esc_html_e( 'Move ticket type', 'event-tickets' ); ?>
			</button>

			<img src="<?php echo esc_url( get_admin_url( null, 'images/spinner.gif' ) ); ?>" id="spinner" />
		</div>
	</div>
</div>