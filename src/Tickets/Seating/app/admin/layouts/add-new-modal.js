import {ACTION_ADD_NEW_LAYOUT, ajaxNonce, ajaxUrl} from '@tec/tickets/seating/ajax';
import {onReady} from '@tec/tickets/seating/utils';

/**
 * @type {string}
 */
const objectName = tec.tickets.seating.layouts.addLayoutModal;

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

/**
 * Handles adding a new layout.
 *
 * @since TBD
 *
 * @return {Promise<void>}
 */
async function addNewLayout() {
	const mapSelect = document.getElementById( 'tec-tickets-seating__select-map' );
	const mapId = mapSelect.selectedOptions[0].value;
	const wrapper = document.querySelector( '.tec-tickets-seating__new-layout-wrapper' );

	if ( ! mapId ) {
		return;
	}

	wrapper.style.opacity = 0.5;

	const result = await addLayoutByMapId(mapId);

	if ( result ) {
		closeModal();
		window.location.href = result.data;
	} else {
		alert( 'Error adding layout' );
		wrapper.style.opacity = 1;
	}
}

/**
 * Adds a new layout by map ID.
 *
 * @since TBD
 *
 * @param {string} mapId The map ID.
 *
 * @return {Promise<boolean|object>} A promise that resolves to data object if the layout was added successfully, false otherwise.
 */
async function addLayoutByMapId( mapId ) {
	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	url.searchParams.set('mapId', mapId);
	url.searchParams.set('action', ACTION_ADD_NEW_LAYOUT);
	const response = await fetch(url.toString(), { method: 'POST' });

	if ( response.status === 200 ) {
		return await response.json();
	}

	return false;
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
