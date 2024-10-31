import {ACTION_ADD_NEW_LAYOUT, ajaxNonce, ajaxUrl} from '@tec/tickets/seating/ajax';
import {onReady, getLocalizedString, redirectTo} from '@tec/tickets/seating/utils';
import { localizedData } from './localized-data';

/**
 * @type {string}
 */
export const { addLayoutModal } = localizedData;

/**
 * Waits for the modal element to be present in the DOM.
 *
 * @return {Promise<Element>} A promise that resolves to the modal element.
 */
export async function waitForModalElement() {
	return new Promise((resolve) => {
		let timeoutId;
		const check = () => {
			if ( window[addLayoutModal] ) {
				clearTimeout(timeoutId);
				resolve( window[addLayoutModal] );
				return;
			}
			timeoutId = setTimeout(check, 50);
		};

		check();
	});
}

/**
 * Registers the modal actions.
 *
 * @since 5.16.0
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
 * @since 5.16.0
 *
 * @param {Event} event The event object.
 *
 * @return {Promise<void>}
 */
export async function addNewLayout( event ) {
	const mapSelect = document.getElementById( 'tec-tickets-seating__select-map' );
	const mapId = mapSelect.selectedOptions[0].value;
	const wrapper = document.querySelector( '.tec-tickets-seating__new-layout-wrapper' );

	if ( ! mapId ) {
		return;
	}

	event.target.disabled = true;
	wrapper.style.opacity = 0.5;

	const result = await addLayoutByMapId(mapId);

	if ( result ) {
		closeModal();
		redirectTo(result.data);
	} else {
		alert( getLocalizedString( 'add-failed', 'layouts' ) );
		wrapper.style.opacity = 1;
		event.target.disabled = false;
	}
}

/**
 * Adds a new layout by map ID.
 *
 * @since 5.16.0
 *
 * @param {string} mapId The map ID.
 *
 * @return {Promise<boolean|object>} A promise that resolves to data object if the layout was added successfully, false otherwise.
 */
export async function addLayoutByMapId( mapId ) {
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
 * @since 5.16.0
 *
 * @param {Event} event The event object.
 *
 * @return {void} Handles the map select updates.
 */
export function handleSelectUpdates(event) {
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
 * @since 5.16.0
 *
 * @return {void} The modal is closed.
 */
export function closeModal() {
	const modal = window?.[addLayoutModal];

	if (!modal) {
		return;
	}

	modal._hide();
}

/**
 * Initializes the modal element once it's loaded.
 *
 * @since 5.16.0
 *
 * @return {void} Initializes the modal element once it's loaded.
 */
export async function init() {
	const modalElement = await waitForModalElement();

	modalElement.on('show', () => {
		modalActionListener();
	});
}

onReady( async () => {
	await init();
});
