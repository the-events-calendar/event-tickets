import { store } from '@moderntribe/common/store';
import { dispatch, select, subscribe } from '@wordpress/data';
import {
	getSeriesProviderFromEvent,
	getSeriesProviderFromSelection,
	getSeriesTitleFromEvent,
	getSeriesTitleFromSelection,
	removeDiscordantProviderNotice,
	showDiscordantProviderNotice,
	subscribeToSeriesChange,
} from '../../series-relationship';

const lockId = 'tec.flexible-tickets.different-ticket-provider';

/**
 * Get the event ticket provider from the TEC store state.
 *
 * @return {string|null} The ticket provider of the event read from the current state, or `null` if not found.
 */
function getEventProviderFromStore() {
	const state = store.getState();
	if (
		!(
			state.tickets &&
			state.tickets.blocks &&
			state.tickets.blocks.ticket &&
			state.tickets.blocks.ticket.provider
		)
	) {
		return null;
	}

	return state.tickets.blocks.ticket.provider;
}

/**
 * Lock the post publish button when the event and series have different ticket providers.
 */
function lockPostPublish() {
	dispatch('core/editor').lockPostSaving(lockId);
}

/**
 * Unlock the post publish button.
 */
function unlockPostPublish() {
	dispatch('core/editor').unlockPostSaving(lockId);
}

/**
 * Get the event title from the current state.
 *
 * This is not "live" but pretty close to it.
 *
 * @return {string} The title of the event read from the current state.
 */
function getEventTitleFromState() {
	return select('core/editor').getEditedPostAttribute('title');
}

/**
 * Toggle the publish lock based on the event and series providers.
 *
 * @param {string|null} eventProvider  The current event ticket provider.
 * @param {string|null} seriesProvider The current series ticket provider.
 * @param {string}      seriesTitle    The title of the series.
 */
function togglePublishLock(eventProvider, seriesProvider, seriesTitle) {
	if (
		eventProvider === seriesProvider ||
		eventProvider === null ||
		seriesProvider === null
	) {
		unlockPostPublish();
		removeDiscordantProviderNotice();

		return;
	}

	lockPostPublish();
	showDiscordantProviderNotice(getEventTitleFromState(), seriesTitle);
}

/**
 * Toggle the publish lock when the series is changed in the metabox dropdown.
 *
 * @param {Event} event The 'change' event dispatched by Select2.
 */
function togglePublishLockFromMetaboxEvent(event) {
	const seriesProvider = getSeriesProviderFromEvent(event);
	const eventProvider = getEventProviderFromStore();
	togglePublishLock(
		eventProvider,
		seriesProvider,
		getSeriesTitleFromEvent(event)
	);
}

/**
 * Toggle the publish lock when the ticket provider is changed in the Ticket Settings section of the Editor.
 */
function togglePublishLockFromTicketSettings() {
	const seriesProvider = getSeriesProviderFromSelection();
	const eventProvider = getEventProviderFromStore();
	const seriesTitle = getSeriesTitleFromSelection();
	togglePublishLock(eventProvider, seriesProvider, seriesTitle);
}

/**
 * Subscribe to the series change event when the metabox is rendered.
 */
function subscribeToSeriesChangeOnStateUpdate() {
	if (!select('core/edit-post').areMetaBoxesInitialized()) {
		// Before metaboxes are initialized, the series metabox is not yet rendered.
		return;
	}

	// Subscribe to the ticket provider change in the ticket settings metabox.
	store.subscribe(togglePublishLockFromTicketSettings);

	subscribeToSeriesChange(togglePublishLockFromMetaboxEvent);
}

// Start by subscribing to core/edit-post section of the WP store.
subscribe(subscribeToSeriesChangeOnStateUpdate, 'core/edit-post');
