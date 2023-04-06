<?php
/**
 * Event Tickets Emails: Main template > Body > Additional Content.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/add-content.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version TBD
 *
 * @since TBD
 *
 * @var Tribe_Template   $this                  Current template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var \WP_Post         $order                 [Global] The order object.
 * @var int              $order_id              [Global] The order ID.
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 */

 if ( empty( $additional_content ) ) {
	return;
 }

?>
<tr>
	<td class="tec-tickets__email-table-content-add-content-container">
		<?php echo wp_kses_post( $additional_content ); ?>
	</td>
</tr>
