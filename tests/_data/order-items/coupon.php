<?php
/*
 * Captured from the same order as fee.php, with a flat 5.00 coupon. The cart keys coupons by a string,
 * which array_merge() keeps, so the coupon's key is not renumbered.
 */
$items = include __DIR__ . '/fee.php';

return [
	0 => $items[0],
	'coupon-9688' => [
		'id' => 9688,
		'type' => 'coupon',
		'coupon_id' => 9688,
		'price' => 5.0,
		'sub_total' => -5.0,
		'display_name' => 'Five off',
		'slug' => 'five-off',
		'quantity' => 1,
		'event_id' => 0,
		'ticket_id' => 0,
	],
];
