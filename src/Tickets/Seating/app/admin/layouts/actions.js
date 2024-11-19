import { ajaxUrl, ajaxNonce, ACTION_DUPLICATE_LAYOUT } from '@tec/tickets/seating/ajax';
import { onReady, getLocalizedString, redirectTo } from '@tec/tickets/seating/utils';


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
		link.addEventListener('click', deleteListener);
	});
}

/**
 * Bind the delete action.
 *
 * @since 5.17.0
 *
 * @param {Event} event The click event.
 */
async function deleteListener(event) {
	event.preventDefault();
	await handleDelete(event.target);
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
		link.addEventListener('click', destructiveEditActionListener);
	});
}

/**
 * Bind the destructive edit action.
 *
 * @since 5.17.0
 *
 * @param {Event} event The click event.
 */
async function destructiveEditActionListener(event) {
	await handleDestructiveEdit(event);
}

/**
 * Handle destructive edit action.
 *
 * @since 5.16.0
 *
 * @param {Event} event The click event.
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

/**
 * Register a duplicate action on all the duplicate layout buttons.
 *
 * @since 5.17.0
 *
 * @param {HTMLDocument|null} dom The document to use to search for the duplicate buttons.
 */
export function registerDuplicateLayoutAction(dom) {
	dom = dom || document;

	dom.querySelectorAll('.duplicate-layout').forEach(function (btn) {
		btn.addEventListener('click', duplicateListener);
	});
}

/**
 * Bind the duplicate action.
 *
 * @since 5.17.0
 *
 * @param {Event} event The click event.
 */
async function duplicateListener(event) {
	event.preventDefault();
	await handleDuplicateAction(event.target);
}

/**
 * Handle the duplicate layout action.
 *
 * @since 5.17.0
 *
 * @param {HTMLButtonElement} target The target button.
 *
 * @return {Promise<void>}
 */
async function handleDuplicateAction(target) {
	const layoutId = target.getAttribute('data-layout-id');
	if (!layoutId) {
		alert(getLocalizedString('duplicate-failed', 'layouts'));
		return;
	}

	target.disabled = false;
	const card = target.closest('.tec-tickets__seating-tab__card');
	card.style.opacity = 0.5;

	const result = await duplicateLayout(layoutId);

	if (!result?.success) {
		alert(getLocalizedString('duplicate-failed', 'layouts' ));
		card.style.opacity = 1;
		target.disabled = false;
		return;
	}

	redirectTo(result.data);
}

/**
 * Duplicate a layout by layout ID.
 *
 * @since 5.17.0
 *
 * @param {string} layoutId The layout ID.
 *
 * @return {Promise<boolean|object>} A promise with an object of the duplicated layout, or false otherwise.
 */
async function duplicateLayout(layoutId) {
	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	url.searchParams.set('layoutId', layoutId);
	url.searchParams.set('action', ACTION_DUPLICATE_LAYOUT);
	const response = await fetch(url.toString(), { method: 'POST' });
	return await response.json();
}

export {
	handleDelete,
	deleteListener,
	handleDuplicateAction,
	duplicateListener,
	handleDestructiveEdit,
	destructiveEditActionListener,
};

onReady(() => registerDeleteAction(document));
onReady(() => registerDestructiveEditAction(document));
onReady(() => registerDuplicateLayoutAction(document));
