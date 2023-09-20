<?php
/**
 * Event Tickets Emails: Main template > Header for the preview.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/header/header-preview.php
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
 * @var bool                               $is_tec_active      Whether `The Events Calendar` is active or not.
 * @var WP_Post|null                       $event              The event post object with properties added by the `tribe_get_event` function.
 *
 * @see tribe_get_event() For the format of the event object.
 */

// If we're not on preview, bail.
if ( empty( $preview ) ) {
	return;
}

$this->template( 'template-parts/header/head/styles' );

?>
<div class="tec-tickets__email-body">

		<?php $this->template( 'template-parts/header/top-link' ); ?>

		<table role="presentation" class="tec-tickets__email-table-main">

			<?php $this->template( 'template-parts/body/header' ); ?>

			<tr>
				<td>
					<table role="presentation" class="tec-tickets__email-table-content">
