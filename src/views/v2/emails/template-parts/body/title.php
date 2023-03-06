<?php
/**
 * Event Tickets Emails: Main template > Body > Title.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/title.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var Tribe_Template  $this  Current template object.
 */

?>
<tr>
	<td>
		<h1 class="tec-tickets__email-table-content-title">
			<?php echo esc_html( $title ); ?>
		</h1>
	</td>
</tr>
