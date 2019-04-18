<?php
/**
 * @var array $incomplete_statuses
 */
?>

<?php esc_html_e( 'Pending Order Completion refers to anything with the following statuses:', 'event-tickets' ); ?>
<ul class="tooltip-list">
	<li><?php echo implode( '</li><li>', $incomplete_statuses ) ?></li>
</ul>
