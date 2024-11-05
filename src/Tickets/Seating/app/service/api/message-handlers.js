import { ajaxUrl, ajaxNonce } from '@tec/tickets/seating/ajax';
import { layoutsHomeUrl, mapsHomeUrl } from './localized-data';

/**
 * The default message handler that will be called when a message is received from the service.
 *
 * @since 5.16.0
 *
 * @param {MessageEvent} event The message event received from the service.
 *
 * @return {void}
 */
export function defaultMessageHandler(event) {
	console.debug('Message received from service', event);
}

/**
 * Sends a POST request to the backend to invalidate the Maps and Layouts cache.
 *
 * @since 5.16.0
 *
 * @return {Promise<boolean>} A promise that will resolve to `true` if the request was successful, `false` otherwise.
 */
export async function invalidateMapsLayoutsCache() {
	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	url.searchParams.set(
		'action',
		'tec_tickets_seating_service_invalidate_maps_layouts_cache'
	);
	const response = await fetch(url.toString(), { method: 'POST' });

	if (response.status !== 200) {
		console.error(
			'Invalidation of maps and layouts cache failed, clean the transients manually to fetch up-to-date maps and layouts from the service.'
		);
		return false;
	}

	return true;
}

/**
 * Sends a POST request to the backend to invalidate the Layouts cache.
 *
 * @since 5.16.0
 *
 * @return {Promise<boolean>} A promise that will resolve to `true` if the request was successful, `false` otherwise.
 */
export async function invalidateLayoutsCache() {
	const url = new URL(ajaxUrl);
	url.searchParams.set('_ajax_nonce', ajaxNonce);
	url.searchParams.set(
		'action',
		'tec_tickets_seating_service_invalidate_layouts_cache'
	);
	const response = await fetch(url.toString(), { method: 'POST' });

	if (response.status !== 200) {
		console.error(
			'Invalidation of layouts cache failed, clean the transients manually to fetch up-to-date layouts from the service.'
		);
		return false;
	}

	return true;
}

/**
 * Fires when a Map is created or updated on the Service.
 *
 * @since 5.16.0
 *
 * @return {void}
 */
export function onMapCreatedUpdated() {
	invalidateMapsLayoutsCache();
}

/**
 * Fires when a Layout is created or updated on the Service.
 *
 * @since 5.16.0
 *
 * @return {void}
 */
export function onLayoutCreatedUpdated() {
	invalidateLayoutsCache();
}

/**
 * Fires when a Seat type is created or updated on the Service.
 *
 * @since 5.16.0
 *
 * @return {void}
 */
export function onSeatTypeCreatedUpdated() {
	invalidateLayoutsCache();
}

/**
 * On request to go to the Maps home from the Service, redirect to the Maps home.
 *
 * @since 5.16.0
 *
 * @return {void}
 */
export function onGoToMapsHome() {
	if (!mapsHomeUrl) {
		console.error('Maps home url not found');
		return;
	}

	window.location.href = mapsHomeUrl;
}

/**
 * On request to go to the Layouts home from the Service, redirect to the Layouts home.
 *
 * @since 5.16.0
 *
 * @return {void}
 */
export function onGoToLayoutsHome() {
	if (!layoutsHomeUrl) {
		console.error('Layouts home url not found');
		return;
	}

	window.location.href = layoutsHomeUrl;
}
