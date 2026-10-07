<?php
/**
 * The button that adds a recurring event ticket, in the classic editor's tickets panel.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/admin-views/recurring-tickets/form-toggle.php
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var bool $hidden Whether the event does not recur yet; the button shows once it does.
 */

?>
<button
	id="recurring_ticket_form_toggle"
	class="button-secondary ticket_form_toggle tribe-button-icon tribe-button-icon-plus"
	aria-label="<?php echo esc_attr_x( 'Add a new recurring event ticket', 'ARIA label for the button to add a new recurring event ticket', 'event-tickets' ); ?>"
	data-ticket-type="recurring"
	<?php echo $hidden ? 'style="display: none"' : ''; ?>
>
	<?php echo esc_html_x( 'New recurring event ticket', 'Recurring event ticket form toggle button text', 'event-tickets' ); ?>
</button>
