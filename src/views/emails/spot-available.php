<?php
/**
 * Event Tickets Plus Emails: Spot Available template.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/spot-available.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 * If you are looking for Event related templates, see in The Events Calendar plugin.
 *
 * @version 5.20.0
 *
 * @since 5.20.0
 *
 * @var Template       $this               Current template object.
 * @var Spot_Available $email              The email object.
 * @var string         $heading            The email heading.
 * @var string         $title              The email title.
 * @var bool           $preview            Whether the email is in preview mode or not.
 * @var string         $additional_content The email additional content.
 * @var bool           $is_tec_active      Whether `The Events Calendar` is active or not.
 * @var Waitlist       $subscriber         The event post object with properties added by the `tribe_get_event` function.
 */

use TEC\Tickets_Plus\Waitlist\Waitlist;
use TEC\Tickets_Plus\Waitlist\Emails\Spot_Available;

defined( 'ABSPATH' ) || exit;

$this->template( 'template-parts/header' );

$this->template( 'template-parts/body/title' );

$this->template( 'template-parts/body/additional-content' );

$this->template( 'template-parts/footer' );
