import { localizedData } from './localized-data';
import './style.pcss';

const { ajaxUrl, ajaxNonce, ACTION_START, ACTION_TIME_LEFT, ACTION_REDIRECT } =
	localizedData;
const selector = '.tec-tickets-seating__timer';

/**
 * @typedef {Object} TimerData
 * @property {number} postId      The post ID of the post to purchase tickets for.
 * @property {string} token       The ephemeral token used to secure the iframe communication with the service.
 * @property {string} redirectUrl The URL to redirect the user to when the timer expires.
 */

/**
 * Finds and returns the data attached to the first valid timer element on the page.
 *
 * Note: multiple timer elements with different datasets are not supported, this function
 * will find the first timer element providing all the required data and return its data.
 *
 * @return {TimerData|null} The data attached to the first valid timer element on the page, or `null` if no
 *                          timer element is found.
 */
export function findTimerData() {
	const timerElements = document.querySelectorAll(selector);

	if (timerElements.length === 0) {
		return null;
	}

	let token = null;
	let postId = null;
	let redirectUrl = null;
	Array.from(timerElements).find((timerElement) => {
		if (
			timerElement.dataset.token &&
			timerElement.dataset.postId &&
			timerElement.dataset.redirectUrl
		) {
			token = timerElement.dataset.token;
			postId = timerElement.dataset.postId;
			redirectUrl = timerElement.dataset.redirectUrl;
			return true;
		}
		return false;
	});

	if (!(token && postId && redirectUrl)) {
		return null;
	}

	return { token, postId, redirectUrl };
}

/**
 * @typedef {Object} TimerStartData
 * @property {number} secondsLeft The number of seconds left in the timer.
 * @property {number} timestamp   The timestamp of the timer start including microseconds.
 */

/**
 * Starts the seat selection timer.
 *
 * @since TBD
 *
 * @return {Promise<TimerStartData|false>} A Promise that resolves to the timer start data or `false` if the timer could not be started.
 */
export async function start() {
	const timerData = findTimerData();

	if (timerData === null) {
		return false;
	}

	const requestUrl = new URL(ajaxUrl);
	requestUrl.searchParams.set('_ajaxNonce', ajaxNonce);
	requestUrl.searchParams.set('action', ACTION_START);
	requestUrl.searchParams.set('token', timerData.token);
	requestUrl.searchParams.set('postId', timerData.postId);
	const response = await fetch(requestUrl.toString(), {
		method: 'POST',
	});

	if (!response.ok) {
		return false;
	}

	const responseJson = await response.json();

	if (!(responseJson.secondsLeft && responseJson.timestamp)) {
		return false;
	}

	return {
		secondsLeft: responseJson.secondsLeft,
		timestamp: responseJson.timestamp,
	};
}
