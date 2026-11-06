<?php
/*
 * The discount line Event Tickets Plus added to a real cart with a flat 5.00 order discount rule. Its rule
 * data holds a DateTime, which the order meta stores as an object.
 */
$items = include __DIR__ . '/fee.php';

return [
	0 => $items[0],
	1 => [
		'id' => 'rule_6XcIbkijTFVW_3',
		'type' => 'discount',
		'price' => -5.0,
		'sub_total' => -5.0,
		'rule_id' => 3,
		'display_name' => 'Buy 3 save 5',
		'ticket_id' => '0',
		'event_id' => '0',
		'quantity' => 1,
		'data' => [
			'id' => 3,
			'type' => 'order-discount',
			'name' => 'Buy 3 save 5',
			'config' => [
				'requirement' => 'quantity',
				'requirementValue' => 2,
				'discountType' => 'flat',
				'discountValue' => 5,
			],
			'scope' => [],
			'status' => 'active',
			'updated_at' => new DateTime( '2026-09-23 22:04:50.000000', new DateTimeZone( 'UTC' ) ),
		],
	],
];
