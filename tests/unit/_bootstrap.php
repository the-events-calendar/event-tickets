<?php
// let's use our autoloader to load our files in unit tests
$common = __DIR__ . '/../../common/src';

require_once $common . '/Tribe/Autoloader.php';

$autoloader = Tribe__Autoloader::instance();
// register common class prefix
$autoloader->register_prefix( 'Tribe__', $common . '/Tribe' );
// register event-tickets class prefix
$autoloader->register_prefix( 'Tribe__Tickets__', __DIR__ . '/../../src/Tribe' );

$autoloader->register_autoloader();

// short-circuit all l10n functions to return the input
\tad\FunctionMockerLe\defineAll( [
	'translate',
	'translate_with_gettext_context',
	'__',
	'esc_attr__',
	'esc_html__',
	'_e',
	'esc_attr_e',
	'esc_html_e',
	'_x',
	'_ex',
	'esc_attr_x',
	'esc_html_x',
	'_n',
	'_nx',
	'_n_noop',
	'_nx_noop',
], function ( $input ) {
	return $input;
} );

// override the `do_action` function with default behaviours
\tad\FunctionMockerLe\define( 'apply_filters', function ( $tag, $value ) {
	return $value;
} );
\tad\FunctionMockerLe\define( 'do_action', function () {
	// no-op
} );
