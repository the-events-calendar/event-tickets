<?php

namespace Tribe\Tickets\Test\Partials;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;

/**
 * Class V2TestCase for snapshot testing.
 * @package Tribe\Tickets\Test\Partials
 */
abstract class V2TestCase extends WPTestCase {

	use MatchesSnapshots;
	use With_Post_Remapping;

	/** @var string Test site home URL (not HTTPS). */
	public $base_url = 'http://wordpress.test/';
}
