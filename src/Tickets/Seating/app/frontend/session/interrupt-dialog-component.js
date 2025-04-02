import { createHtmlComponentFromTemplateString } from '@tec/tickets/seating/utils';

/**
 * @typedef {Object} InterruptDialogComponentProps
 * @property {string} dataJs      The data-js attribute to use for the dialog content.
 * @property {string} title       The title of the modal.
 * @property {string} content     The content of the modal.
 * @property {string} redirectUrl The URL to redirect the user to when the timer expires.
 */

/**
 * Builds and returns the interrupt dialog component.
 *
 * @since 5.16.0
 *
 * @param {InterruptDialogComponentProps} props The props to use to build the component.
 *
 * @return {HTMLElement|null} The interrupt dialog component, or `null` if the
 *                             template counld not be found or the element could not be created.
 */
export function InterruptDialogComponent(props) {
	return createHtmlComponentFromTemplateString(
		`<script
		id="tec-tickets-seating-interrupt-dialog-template"
		type="text/template"
		data-js="{dataJs}"
	>
		<div class="tribe-tickets-seating__interrupt-dialog" role="dialog">
			<div class="tribe-tickets-seating__interrupt-header">
				<div class="dashicons dashicons-clock"></div>
				<div class="tribe-tickets-seating__interrupt-title">{title}</div>
			</div>
			<div class="tribe-dialog__content tribe-modal__content tribe-tickets-seating__interrupt-content">{content}</div>
			<div class="tribe-tickets-seating__interrupt-footer">
				<button
					type="button"
					onclick="window.location.href='{redirectUrl}'"
					class="tribe-common-c-btn tribe-common-c-btn--small"
				>
					{buttonLabel}
				</button>
		</div>
	</script>`,
		props
	);
}
