<?php

namespace TEC\Tickets\Commerce;

/**
 * Class Attendee
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce
 */
class Attendee {
	/**
	 * Tickets Commerce Attendee Post Type slug.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const POSTTYPE = 'tec_tickets_commerce_attendee';

	/**
	 * Which meta holds the Relation ship between an attendee and which event it's registered to.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $event_relation_meta_key = '_tec_tickets_commerce_event';

	/**
	 * Which meta holds the Relation ship between an attendee and which ticket it was created from.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $ticket_relation_meta_key = '_tec_tickets_commerce_ticket';

	/**
	 * Which meta holds the Relation ship between an attendee and which order it belongs to.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $order_relation_meta_key = '_tec_tickets_commerce_order';

	/**
	 * Which meta holds the purchaser name for an attendee.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $purchaser_name_meta_key = '_tec_tickets_commerce_purchaser_name';

	/**
	 * Which meta holds the purchaser email for an attendee.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $purchaser_email_meta_key = '_tec_tickets_commerce_purchaser_email';

	/**
	 * Which meta holds the security code for an attendee.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $security_code_meta_key = '_tec_tickets_commerce_security_code';

	/**
	 * Which meta holds the optout value for an attendee.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $optout_meta_key = '_tec_tickets_commerce_optout';

	/**
	 * Which meta holds the checked in status for an attendee.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $checked_in_meta_key = '_tec_tickets_commerce_checked_in';
}