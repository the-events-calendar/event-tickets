<?php
/**
 * The base test case that should be used to test HTML partials.
 *
 * @package Tribe\Tickets\Test\Testcases;
 */

namespace Tribe\Tickets\Test\Testcases;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;
use Tribe\Test\Products\WPBrowser\Views\V2\PartialTestCase;

/**
 * Class Html_Partial_Test_Case
 *
 * @package Tribe\Events\Virtual\Tests\Test_Cases
 */
class Html_Partial_Test_Case extends PartialTestCase {
	use SnapshotAssertions;
	use With_Post_Remapping;
}
