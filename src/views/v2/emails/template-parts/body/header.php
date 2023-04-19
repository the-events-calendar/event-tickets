<?php
/**
 * Event Tickets Emails: Main template > Body > Header.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/header.php
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
	<td
		class="tec-tickets__email-table-main-header"
		align="<?php echo esc_attr( $header_image_alignment ); ?>"
	>
		<?php $this->template( 'template-parts/body/header/image' ); ?>
	</td>
</tr>
