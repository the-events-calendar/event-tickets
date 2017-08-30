<?php
/**
 * @var string $text
 * @var string $input_id
 */
?>

<p class="search-box" style="margin-bottom: 10px;">
	<label for="s"><?php echo $text; ?>:</label>
	<input type="search" style="float:none;" id="<?php echo $input_id; ?>" name="s" value="<?php _admin_search_query(); ?>"/>
	<?php submit_button( esc_html__( 'Search attendees', 'event-tickets' ), '', '', false, array( 'id' => 'search-submit' ) ); ?>
</p>
