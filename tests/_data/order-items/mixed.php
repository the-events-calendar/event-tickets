<?php
/*
 * The keys a real order gets when the fee, coupon and discount filters run in that order: array_merge()
 * renumbers the integer keys and keeps the coupon's string key.
 */
$fee      = include __DIR__ . '/fee.php';
$coupon   = include __DIR__ . '/coupon.php';
$discount = include __DIR__ . '/discount.php';

return [
	0 => $fee[0],
	1 => $fee[1],
	'coupon-9688' => $coupon['coupon-9688'],
	2 => $discount[1],
];
