<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

   /**
    * Define custom actions here
    */

   	/**
	 * Sets tickets enabled post types
	 *
	 * @param  array $post_types The post types to be enabled for tickets. Can be empty.
	 */
	public function haveTicketablePostTypes ( array $post_types = array( ) ) {

		$I = $this;

		$post_types_str = ( empty( $post_types ) ) ? '' : implode( "','", $post_types );

		$code = <<< PHP
add_filter( 'tribe_tickets_post_types', 'test_ticketable_post_types' );
function test_ticketable_post_types( ) {
	return array( {$post_types_str} );
}
PHP;

		$I->haveMuPlugin('ticketable-post-types.php',$code);
	}

	/**
	 * Activates The Events Calendar
	 */
	public function haveTheEventsCalendarActive ( ) {

		$I = $this;

		$active_plugins = $I->grabOptionFromDatabase( 'active_plugins' );
		$active_plugins[] = 'the-events-calendar';
		$I->haveOptionInDatabase( 'active_plugins', $active_plugins );
	}
}
