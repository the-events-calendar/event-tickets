<?php
/*
 * Hand-built: the integer key 1 and the string key '01' must both survive,
 * neither overwriting the other.
 */
$items = include __DIR__ . '/tickets.php';

return [
	'01' => $items[0],
	1    => $items[1],
];
