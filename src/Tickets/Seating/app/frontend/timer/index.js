import { localizedData } from './localized-data';
import './style.pcss';
import { doAction } from '@wordpress/hooks';

const { ajaxUrl, ajaxNonce, ACTION_START, ACTION_SYNC } = localizedData;

/**
 * The selector used to find the timer elements on the page.
 *
 * @since TBD
 *
 * @type {string}
 */
const selector = '.tec-tickets-seating__timer';

/**
 * The class name that, applied to the timer elements, will hide them.
 *
 * @since TBD
 *
 * @type {string}
 */
const hiddenClassName = 'tec-tickets-seating__timer--hidden';

/**
 * The ID of the countdown loop that will update the timer every second.
 *
 * @since TBD
 *
 * @type {?number}
 */
let countdownLoopId = null;

/**
 * The ID of the health check loop that will sync the timer with the backend every minute.
 *
 * @since TBD
 *
 * @type {?number}
 */
let healthCheckLoopId = null;

/**
 * Whether the timer has been started or not.
 *
 * @since TBD
 *
 * @type {boolean}
 */
let started = false;

/**
 * @typedef {Object} TimerData
 * @property {number} postId      The post ID of the post to purchase tickets for.
 * @property {string} token       The ephemeral token used to secure the iframe communication with the service.
 * @property {string} redirectUrl The URL to redirect the user to when the timer expires.
 */

/**
 * Returns all the timer elements on the page.
 *
 * @since TBD
 *
 * @return {NodeList<HTMLElement>} All the timer elements on the page.
 */
function getTimerElements() {
	return document.querySelectorAll(selector);
}

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
	const sourceTimerElements = getTimerElements();

	if (sourceTimerElements.length === 0) {
		return null;
	}

	let token = null;
	let postId = null;
	let redirectUrl = null;
	Array.from(sourceTimerElements).find((timerElement) => {
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
 * Sets the minutes and seconds of the timer.
 *
 * @since TBD
 *
 * @param {HTMLElement} timerElement The timer element to set the minutes and seconds for..
 * @param {number}      minutes
 * @param {number}      seconds
 */
function setTimerTimeLeft(timerElement, minutes, seconds) {
	timerElement.classList.remove(hiddenClassName);
	const minutesElement = timerElement.querySelector(
		'.tec-tickets-seating__time-minutes'
	);
	const secondsElement = timerElement.querySelector(
		'.tec-tickets-seating__time-seconds'
	);

	if (!minutesElement || !secondsElement) {
		return;
	}

	minutesElement.innerText = minutes;
	secondsElement.innerText = String(seconds).padStart(2, '0');
}

/**
 * Interrupts the user triggering the user flow redirection when the time is up.
 *
 * @since TBD
 *
 * @return {void} The timer is interrupted.
 */
function interrupt() {
	getTimerElements().forEach((timerElement) => {
		setTimerTimeLeft(timerElement, 0, 0);
	});

	clearTimeout(countdownLoopId);
	clearTimeout(healthCheckLoopId);

	console.log('Interrupting the timer');

	/**
	 * Fires to trigger an interruption of the user flow due to the timer expiring.
	 *
	 * @since TBD
	 */
	doAction('tec.tickets.seating.timer_interrupt');
}

/**
 * Starts the loop that will recursively update the timer(s) every second.
 *
 * @since TBD
 *
 * @param {number} secondsLeft The number of seconds left in the timer.
 *
 * @return {void}
 */
function startCountdownLoop(secondsLeft) {
	if (secondsLeft <= 0) {
		interrupt();
	}

	countdownLoopId = setTimeout(() => {
		secondsLeft -= 1;
		getTimerElements().forEach((timerElement) => {
			setTimerTimeLeft(
				timerElement,
				Math.floor(secondsLeft / 60),
				secondsLeft % 60
			);
		});
		startCountdownLoop(secondsLeft);
	}, 1000);
}

/**
 * Starts a loop to sync the timer with the backend every minute.
 *
 * @since TBD
 *
 * @return {void}
 */
function startHealthCheckLoop() {
	healthCheckLoopId = setTimeout(async () => {
		await syncWithBackend();
		startHealthCheckLoop();
	}, 60 * 1000);
}

/**
 * Sends a request to the backend to get the timer's seconds left.
 *
 * If the seconds left is less than or equal to 0, the interruption logic will be triggered.
 *
 * @since TBD
 *
 * @return {Promise<void>} A promise that will resolve when the request is completed.
 */
export async function syncWithBackend() {
	if (getTimerElements().length === 0) {
		return;
	}

	const secondsLeft = await requestToBackend(ACTION_SYNC);

	if (secondsLeft <= 0) {
		interrupt();
	}

	if (countdownLoopId) {
		clearTimeout(countdownLoopId);
	}

	startCountdownLoop(secondsLeft);
}

/**
 * Sends a request to the backend to either start or syncthe timer.
 *
 * @since TBD
 *
 * @param {ACTION_START|ACTION_SYNC} action The action to send to the backend.
 *
  @return {Promise<number|boolean>} A promise that will resolve to the number of seconds left
 *                                  in the timer or `false` if the request failed.
 */
async function requestToBackend(action) {
	const timerData = findTimerData();

	if (timerData === null) {
		return false;
	}

	if ([ACTION_START, ACTION_SYNC].indexOf(action) === -1) {
		return false;
	}

	const requestUrl = new URL(ajaxUrl);
	requestUrl.searchParams.set('_ajaxNonce', ajaxNonce);
	requestUrl.searchParams.set('action', action);
	requestUrl.searchParams.set('token', timerData.token);
	requestUrl.searchParams.set('postId', timerData.postId);
	const response = await fetch(requestUrl.toString(), {
		method: 'POST',
	});

	if (!response.ok) {
		return false;
	}

	const responseJson = await response.json();

	if (
		!(
			responseJson.success &&
			responseJson.data.secondsLeft &&
			responseJson.data.timestamp
		)
	) {
		console.error('Failed to communicate with the backend');

		return false;
	}

	/**
	 * @typedef {Object} StartTimerResponse
	 * @property {number} secondsLeft The number of seconds left in the timer.
	 * @property {number} timestamp   The timestamp of the timer start as a Unix timestamp with microseconds.
	 *                                This is what the PHP function `microtime` returns.
	 */

	/**
	 * @type {StartTimerResponse}
	 */
	const startTimerResponse = responseJson.data;

	/*
	 * The browser and the backend might not produce the same exact time.
	 * Do not allow the time to increase due to the browser's inaccuracy.
	 */
	return (
		startTimerResponse.secondsLeft -
		Math.max(
			0,
			Math.floor(startTimerResponse.timestamp - Date.now() / 1000)
		)
	);
}

/**
 * Starts the seat selection timer on the backend and frontend of the site.
 *
 * @since TBD
 *
 * @return {Promise<void>} A Promise that resolves when the timer is started.
 */
export async function start() {
	if (started || getTimerElements().length === 0) {
		return;
	}

	const secondsLeft = await requestToBackend(ACTION_START);

	if (!secondsLeft) {
		// The timer could not be started, communication with the backend failed. Restart the flow.
		interrupt();
		return;
	}

	const minutes = Math.floor(secondsLeft / 60);
	const seconds = secondsLeft % 60;

	getTimerElements().forEach((timerElement) => {
		setTimerTimeLeft(timerElement, minutes, seconds);
	});

	started = true;

	startCountdownLoop(secondsLeft);
	startHealthCheckLoop();
}

/**
 * Resets the timer cancelling any pending countdown and health check loops and setting the started flag to false.
 *
 * @since TBD
 *
 * @return {void} The timer is reset.
 */
export function reset() {
	if (countdownLoopId) {
		clearTimeout(countdownLoopId);
	}

	if (healthCheckLoopId) {
		clearTimeout(healthCheckLoopId);
	}

	started = false;
}

/**
 * Syncs the timer with the backend on DOM ready.
 *
 * @since TBD
 *
 * @return {void} The timer is synced.
 */
function syncOnLoad() {
	const syncTimerElements = Array.from(getTimerElements()).filter(
		(syncTimerElement) => {
			return 'syncOnLoad' in syncTimerElement.dataset;
		}
	);

	if (syncTimerElements.length === 0) {
		return;
	}

	syncWithBackend();
}

// On DOM ready check if any timer needs to be synced.
if (document.readyState !== 'loading') {
	syncOnLoad();
} else {
	document.addEventListener('DOMContentLoaded', syncOnLoad);
}

window.tec = window.tec || {};
window.tec.tickets = window.tec.tickets || {};
window.tec.tickets.seating = window.tec.tickets.seating || {};
window.tec.tickets.seating.frontend = window.tec.tickets.seating.frontend || {};
window.tec.tickets.seating.frontend.timer = {
	...(window.tec.tickets.seating.frontend.timer || {}),
	start,
	reset,
	syncWithBackend,
	interrupt,
	getTimerElements,
};
