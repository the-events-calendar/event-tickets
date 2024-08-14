import { ajaxUrl, ajaxNonce } from '@tec/tickets/seating/ajax';
import { onReady, getLocalizedString } from '@tec/tickets/seating/utils';

const objectName = 'dialog_obj_tec-tickets-seating-layouts-modal';

/**
 * Waits for the modal element to be present in the DOM.
 *
 * @return {Promise<Element>} A promise that resolves to the modal element.
 */
async function waitForModalElement() {
	return new Promise((resolve) => {
		const check = () => {
			if (window[objectName]) {
				resolve(window[objectName]);
			}
			setTimeout(check, 50);
		};

		check();
	});
}

/**
 * Registers the modal actions.
 *
 * @since TBD
 *
 * @return {void} Handles the modal actions.
 */
export function modalActionListener() {
	const mapSelect = document.getElementById( 'tec-tickets-seating__select-map' );
	mapSelect?.addEventListener( 'change', handleSelectUpdates );

	const cancelButton = document.querySelector( '.tec-tickets-seating__new-layout-button-cancel' );
	cancelButton?.addEventListener( 'click', closeModal );

	const addButton = document.querySelector( '.tec-tickets-seating__new-layout-button-add' );
	addButton?.addEventListener( 'click', addNewLayout );
}

export function addNewLayout() {
	const mapSelect = document.getElementById( 'tec-tickets-seating__select-map' );
	const mapId = mapSelect.selectedOptions[0].value;

	if ( mapId ){
		alert( 'Map selected' + mapId );
	}
}

/**
 * Handles the map select updates.
 *
 * @since TBD
 *
 * @param {Event} event The event object.
 *
 * @return {void} Handles the map select updates.
 */
export function handleSelectUpdates( event ) {
	const selectedOption = event.target.options[event.target.selectedIndex];

	const img = document.getElementById( 'tec-tickets-seating__new-layout-map-preview-img' );
	img.src = selectedOption.getAttribute('data-screenshot-url');;

	const seatsCountElement = document.querySelector( '.tec-tickets-seating__new-layout-map-seats-count' );
	seatsCountElement.innerHTML = selectedOption.getAttribute('data-seats-count');

	const mapNameElement = document.querySelector( '.tec-tickets-seating__new-layout-map-name' );
	mapNameElement.innerHTML = selectedOption.innerHTML;
}

/**
 * Closes the modal element using its reference on the window object.
 *
 * @since TBD
 *
 * @return {void} The modal is closed.
 */
export function closeModal() {
	const modal = window?.[objectName];

	if (!modal) {
		return;
	}

	modal._hide();
}

onReady(() => {
	waitForModalElement().then((modalElement) => {
		modalElement.on('show', () => {
			modalActionListener();
		});
	});
})
