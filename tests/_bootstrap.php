<?php
/**
 * @file Global bootstrap for all codeception tests
 */

Codeception\Util\Autoload::addNamespace( 'Tribe__Events__WP_UnitTestCase', __DIR__ . '/_support' );
Codeception\Util\Autoload::addNamespace( 'Tribe\Events\Test', __DIR__ . '/_support' );
Codeception\Util\Autoload::addNamespace( 'Tribe\Tickets\Test', __DIR__ . '/_support' );
