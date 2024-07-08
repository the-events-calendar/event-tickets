import {ajaxUrl, ajaxNonce} from "@tec/tickets/seating/service";
import {onReady, getLocalizedString} from "@tec/tickets/seating/utils";

/**
 * Get localized string for the given key.
 *
 * @since TBD
 *
 * @param {string} key - The key to get the localized string for.
 *
 * @returns {string} - The localized string.
 */
export function getString(key) {
	return getLocalizedString(key, 'layouts');
}

/**
 * Register delete action on all links with class 'delete-layout'.
 *
 * @since TBD
 */
export function register_delete_action() {
	// Add click listener to all links with class 'delete'.
	document.querySelectorAll('.delete-layout').forEach(function(link) {
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
	const layoutId = element.getAttribute('data-layout-id');
	const mapId = element.getAttribute('data-map-id');

	const card = element.closest('.tec-tickets__seating-tab__card');
	card.style.opacity = 0.5;

	if (confirm(getString('delete-confirmation'))) {
		const result = await delete_layout(layoutId, mapId);
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
 * Delete layout.
 *
 * @since TBD
 *
 * @param {string} layoutId - The layout ID.
 * @param {string} mapId - The map ID.
 *
 * @returns {Promise<boolean>} - Promise resolving to true if delete was successful, false otherwise.
 */
async function delete_layout(layoutId, mapId) {
	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	url.searchParams.set('layoutId', layoutId);
	url.searchParams.set('mapId', mapId);
	url.searchParams.set(
		'action',
		'tec_tickets_seating_service_delete_layout'
	);
	const response = await fetch(url.toString(), { method: 'POST' });

	return response.status === 200;
}

export { handleDelete, delete_layout };

onReady(register_delete_action);