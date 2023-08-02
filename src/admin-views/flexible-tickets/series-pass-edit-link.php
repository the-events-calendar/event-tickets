<?php
/**
 * @var string $series_edit_link The Series edit link.
 */

$edit_text = _x(
	'Edit in Series',
	'Text for the link to edit a Series Pass in the Series from the ticket editor',
	'event-tickets'
)
?>

<a href="<?php echo esc_url( $series_edit_link ); ?>" target="_blank">
	<?php echo esc_html( $edit_text ); ?>
</a>
