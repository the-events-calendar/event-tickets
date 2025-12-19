<?php
/**
 * A trait that provides a `@before` methods to handle transactions in tests.
 *
 * WordPress tests already managed the database state through a transaction started before each
 * test and rolled back after it.
 * This is done non-aggressively, though, to let the tester decide when COMMITting or rolling-back
 * the transaction is appropriate. I.e., `COMMIT` and `ROLLBACK` that are called by tests, or by
 * code under test, will not be prevented by the test case by default.
 *
 * Use this trait to ensure that `COMMIT` and `ROLLBACK` are correctly handled in the context
 * of tests.
 * - COMMIT - do nothing.
 * - ROLLBACK - roll back and immediately start a new transaction.
 *
 * Usage:
 * ```php
 * class Some_Feature_That_Uses_Transactions extends WPTestCase {
 *     use Traits\With_No_Query_Commit;
 *
 *     public function setUp(): void {
 *         parent::setUp();
 *         // After the test case started transaction start filtering.
 *         $this->filter_query_to_avoid_commit_rollback();
 *     }
 *
 *     public function tearDown():void{
 *         // Remove the filter before the test case uses ROLLBACK.
 *         $this->remove_filter_query_to_avoid_commit_rollback();
 *         parent::tearDown();
 *     }
 * }
 * ```
 *
 * Note that, due to the order of operations of `@before` and `@after` methods changing depending on the PHPUnit
 * version the "safe" option is to use the methods provided by this trait deterministically, in the test case `setUp`
 * and `tearDown` methods.
 *
 * Since ROLLBACK operation cannot be cleanly performed generically, the test case using this trait must
 * implement a method named `avoid_query_commit_rollback_handler( string $query ) :string` that will be used to
 * handle ROLLBACK queries.
 *
 * @package Traits;
 */

namespace Traits;

/**
 * Trait With_No_Query_Commit.
 *
 * @since   TBD
 *
 * @package Traits;
 */
trait With_No_Query_Commit {
	public function avoid_query_commit_rollback( string $query ) {
		if (
			strcasecmp( 'START', $query ) === 0
			|| strcasecmp( 'BEGIN', $query ) === 0
			|| strcasecmp( 'START TRANSACTION', $query ) === 0
			|| strcasecmp( 'BEGIN TRANSACTION', $query ) === 0
		) {
			// Do not start a transaction, one is already started.
			return 'SELECT 1';
		}

		if ( strcasecmp( 'ROLLBACK', $query ) === 0 ) {
			// A ROLLBACK action cannot be cleanly done since it would roll back the fixture data as well.
			if ( ! method_exists( $this, 'avoid_query_commit_rollback_handler' ) ) {
				$message = 'ROLLBACK handler not found: please define a method named ' .
				           '"avoid_query_commit_rollback_handler( string $query ) :string" in your test case. ' .
				           'If you need real transaction logic in tests, then test the subject under test in a ' .
				           'suite using the WPLoader module with "loadOnly: true"';
				throw new \RuntimeException( $message );
			}

			// The test case provides a rollback handler, let's use it.
			return $this->avoid_query_commit_rollback_handler( $query );
		}

		if ( strcasecmp( 'COMMIT', $query ) === 0 ) {
			// If the query is COMMIT, don't run it and let the query proceed.
			return 'SELECT 1';
		}

		// Not a query we're interested in filtering.
		return $query;
	}

	/**
	 * Hooks the filters required to avoid COMMIT and ROLLBACK operations in tests.
	 *
	 * Use this method in the test case `setUp()` method.
	 */
	protected function filter_query_to_avoid_commit_rollback(): void {
		add_filter( 'query', [ $this, 'avoid_query_commit_rollback' ], - 100 );
	}

	/**
	 * Removes the filters required to avoid COMMIT and ROLLBACK operations in tests.
	 *
	 * Use this method in the test case `tearDown()` method.
	 */
	protected function remove_filter_query_to_avoid_commit_rollback(): void {
		remove_filter( 'query', [ $this, 'avoid_query_commit_rollback' ], - 100 );
	}
}
