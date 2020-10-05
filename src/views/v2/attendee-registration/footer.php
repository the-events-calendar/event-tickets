<?php
/**
 * Attendee registration
 * Footer
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/attendee-registration/footer.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var int $non_meta_count The number of tickets in cart without ARI (Attendee Registration Information).
 */

?>
<div class="tribe-tickets__registration__footer">
	<?php
	$notice_classes = [
		'tribe-tickets__notice--non-ar',
		'tribe-common-a11y-hidden', // Set as hidden. JavaScript will show it if needed.
	];

	$this->template(
		'components/notice',
		[
			'notice_classes' => $notice_classes,
			'content'        => sprintf(
				// Translators: %s HTML wrapped number of tickets.
				esc_html_x(
					'There are %s other tickets in your cart that do not require attendee information.',
					'Note that there are more tickets in the cart, %s is the html-wrapped number.',
					'event-tickets'
				),
				'<span id="tribe-tickets__non-ar-count">' . absint( $non_meta_count ) . '</span>'
			)
		]
	);

	$this->template( 'v2/attendee-registration/button/submit' );
	?>
</div>
