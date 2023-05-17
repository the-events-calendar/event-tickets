<?php
/**
 * Event Tickets Emails: Main template > Header > Head > JSON-LD.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/header/head/json-ld.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var Tribe__Template                    $this               Current template object.
 * @var \TEC\Tickets\Emails\Email_Abstract $email              The email object.
 * @var string                             $heading            The email heading.
 * @var string                             $title              The email title.
 * @var bool                               $preview            Whether the email is in preview mode or not.
 * @var string                             $additional_content The email additional content.
 * @var bool             $is_tec_active      Whether `The Events Calendar` is active or not.
 * @var JSON_LD_Abstract $json_ld  The JSON-LD object.
 *
 * @see tribe_get_event() For the format of the event object.
 */

use \TEC\Tickets\Emails\JSON_LD\JSON_LD_Abstract;
if ( ! empty( $preview ) || empty( $json_ld ) ) {
	return;
}
?>
<script type="application/ld+json">
	<?php echo wp_json_encode( $json_ld->get_data(), $json_ld->get_json_encode_options() ); ?>
</script>
