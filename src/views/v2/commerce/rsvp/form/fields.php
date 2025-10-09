<?php
/**
 * Block: RSVP
 * Form fields
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/rsvp/form/fields.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 */

?>
<?php
$this->template(
	'v2/commerce/rsvp/form/fields/name',
	[
		'rsvp'  => $rsvp,
		'going' => $going,
	]
);
?>
<?php
$this->template(
	'v2/commerce/rsvp/form/fields/email',
	[
		'rsvp'  => $rsvp,
		'going' => $going,
	]
);
?>
<?php
$this->template(
	'v2/commerce/rsvp/form/fields/quantity',
	[
		'rsvp'  => $rsvp,
		'going' => $going,
	]
);
?>
