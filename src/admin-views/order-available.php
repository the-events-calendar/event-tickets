<?php
/**
 * @var array $available
 */
?>

<div>
	<?php echo esc_html__( 'Availability for this ticket type is counted using', 'event-tickets' ) . ', ' . esc_html( array_search( min( $available ), $available ) . ' - ' . min( $available ) ); ?>
</div>
<p>
	<a href="https://m.tri.be/1aek" target="_blank"><?php esc_html_e( 'Learn more about how Availability is calculated.', 'event-tickets' ); ?></a>
</p>
