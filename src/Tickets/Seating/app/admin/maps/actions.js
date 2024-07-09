import {ajaxUrl, ajaxNonce} from "@tec/tickets/seating/service";
import {onReady, getLocalizedString} from "@tec/tickets/seating/utils";

/**
 * Get localized string for the given key.
 *
 * @param {string} key - The key to get the localized string for.
 *
 * @returns {string} - The localized string.
 */
export function getString(key) {
	return getLocalizedString(key, 'maps');
}

/**
 * Register delete action on all links with class 'delete-map'.
 */
export function register_delete_action() {
	// Add click listener to all links with class 'delete'.
	document.querySelectorAll('.delete-map').forEach(function(link) {
		link.addEventListener('click', async function(event) {
			event.preventDefault();
			await handleDelete(event.target);
		});
	});
}

/**
 * Handle delete action.
 *
 * @since TBD
 *
 * @param {HTMLElement} element - The target item.
 *
 * @return {Promise<void>}
 */
async function handleDelete(element) {
	const mapId = element.getAttribute('data-map-id');
	const card = element.closest('.tec-tickets__seating-tab__card');

	card.style.opacity = 0.5;

	if (confirm(getString('delete-confirmation'))) {
		const result = await delete_map(mapId);
		if (result) {
			window.location.reload();
		} else {
			card.style.opacity = 1;
			alert(getString('delete-failed'));
		}
	} else {
		card.style.opacity = 1;
	}
}

/**
 * Delete map by ID.
 *
 * @since TBD
 *
 * @param {string} mapId - The map ID.
 *
 * @returns {Promise<boolean>} - Promise resolving to true if delete was successful, false otherwise.
 */
async function delete_map(mapId) {
	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	url.searchParams.set('mapId', mapId);
	url.searchParams.set(
		'action',
		'tec_tickets_seating_service_delete_map'
	);
	const response = await fetch(url.toString(), { method: 'POST' });

	return response.status === 200;
}

export { handleDelete, delete_map };

onReady(register_delete_action);