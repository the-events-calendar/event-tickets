import { ajaxUrl, ajaxNonce } from '@tec/tickets/seating/ajax';
import { onReady, getLocalizedString } from '@tec/tickets/seating/utils';

/**
 * Get localized string for the given key.
 *
 * @since 5.16.0
 *
 * @param {string} key The key to get the localized string for.
 *
 * @return {string} The localized string.
 */
export function getString(key) {
	return getLocalizedString(key, 'layouts');
}

/**
 * Register delete action on all links with class 'delete-layout'.
 *
 * @since 5.16.0
 *
 * @param {HTMLDocument|null} dom The document to use to search for the delete buttons.
 */
export function registerDeleteAction(dom) {
	dom = dom || document;
	// Add click listener to all links with class 'delete'.
	dom.querySelectorAll('.delete-layout').forEach(function (link) {
		link.addEventListener('click', async function (event) {
			event.preventDefault();
			await handleDelete(event.target);
		});
	});
}

/**
 * Handle delete action.
 *
 * @since 5.16.0
 *
 * @param {HTMLElement} element The target item.
 *
 * @return {Promise<void>}
 */
async function handleDelete(element) {
	const layoutId = element.getAttribute('data-layout-id');
	const mapId = element.getAttribute('data-map-id');

	if (!(layoutId && mapId)) {
		return;
	}

	const card = element.closest('.tec-tickets__seating-tab__card');
	card.style.opacity = 0.5;

	if (confirm(getString('delete-confirmation'))) {
		const result = await deleteLayout(layoutId, mapId);

		if (result) {
			window.location.reload();
			return;
		}

		card.style.opacity = 1;
		alert(getString('delete-failed'));
		return;
	}

	card.style.opacity = 1;
}

/**
 * Delete layout.
 *
 * @since 5.16.0
 *
 * @param {string} layoutId The layout ID.
 * @param {string} mapId    The map ID.
 *
 * @return {Promise<boolean>} Promise resolving to true if delete was successful, false otherwise.
 */
async function deleteLayout(layoutId, mapId) {
	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	url.searchParams.set('layoutId', layoutId);
	url.searchParams.set('mapId', mapId);
	url.searchParams.set('action', 'tec_tickets_seating_service_delete_layout');
	const response = await fetch(url.toString(), { method: 'POST' });

	return response.status === 200;
}

/**
 * Register destructive edit action on all links with class 'edit-layout'.
 *
 * @since 5.16.0
 *
 * @param {HTMLDocument|null} dom The document to use to search for the edit buttons.
 */
export function registerDestructiveEditAction(dom) {
	dom = dom || document;
	// Add click listener to all links with class 'delete'.
	dom.querySelectorAll('.edit-layout').forEach(function (link) {
		link.addEventListener('click', async function (event) {
			handleDestructiveEdit(event);
		});
	});
}

/**
 * Handle destructive edit action.
 *
 * @since 5.16.0
 *
 * @param {ClickEvent} event The click event.
 *
 * @return {Promise<void>}
 */
async function handleDestructiveEdit(event) {
	const associatedEvents = event.target.getAttribute('data-event-count');

	if ( Number(associatedEvents) > 0 ) {
		const card = event.target.closest('.tec-tickets__seating-tab__card');
		card.style.opacity = 0.5;

		if (
			!confirm(
				getString('edit-confirmation').replace(
					'{count}',
					associatedEvents
				)
			)
		) {
			card.style.opacity = 1;
			event.preventDefault();
		}
	}
}

export { handleDelete, deleteLayout };

onReady(() => registerDeleteAction(document));
onReady(() => registerDestructiveEditAction(document));
