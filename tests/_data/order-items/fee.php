<?php
// Captured from an order with a flat 2.50 fee on a 20.00 ticket, built with Fee_Creator and Order_Maker.
return [
	0 => [
		'event_id' => 5096,
		'extra' => [],
		'price' => 20.0,
		'quantity' => 2,
		'regular_price' => 20.0,
		'regular_sub_total' => 40.0,
		'sub_total' => 40.0,
		'ticket_id' => 5097,
		'type' => 'ticket',
	],
	1 => [
		'id' => 'fee_9687_5097',
		'type' => 'fee',
		'price' => 2.5,
		'sub_total' => 5.0,
		'fee_id' => 9687,
		'display_name' => 'Service fee',
		'ticket_id' => 5097,
		'event_id' => '0',
		'quantity' => 2,
	],
];
