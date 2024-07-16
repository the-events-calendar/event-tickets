import { localizedData } from './localized-data';
import './style.pcss';
import { doAction, applyFilters } from '@wordpress/hooks';
import { InterruptDialogComponent } from './interrupt-dialog-component';
import { _x } from '@wordpress/i18n';
import { onReady } from '@tec/tickets/seating/utils';

const {
	ajaxUrl,
	ajaxNonce,
	ACTION_START,
	ACTION_SYNC,
	ACTION_INTERRUPT_GET_DATA,
} = localizedData;

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
 * The interrupt dialog HTML element.
 *
 * @since TBD
 *
 * @type {HTMLElement|null}
 */
let interruptDialogElement = null;

/**
 * The selectors used to find the checkout controls on the page.
 *
 * @since TBD
 *
 * @type {string}
 */
export const checkoutControlsSelectors =
	'.tribe-tickets__commerce-checkout-form-submit-button';

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
 * @typedef {Object} InterruptModalData
 * @property {string} title       The title of the modal.
 * @property {string} content     The content of the modal.
 * @property {string} redirectUrl The URL to redirect the user to when the timer expires.
 */

/**
 * Fetches the data required to render the correct timer expiration modal on the frontend.
 *
 * @since TBD
 *
 * @return {Promise<InterruptModalData>} The data required to render the correct timer expiration modal on the frontend.
 */
async function fetchInterruptModalData() {
	const { postId, token } = findTimerData();
	const requestUrl = new URL(ajaxUrl);
	requestUrl.searchParams.set('_ajaxNonce', ajaxNonce);
	requestUrl.searchParams.set('action', ACTION_INTERRUPT_GET_DATA);
	requestUrl.searchParams.set('postId', postId);
	requestUrl.searchParams.set('token', token);

	const response = await fetch(requestUrl.toString(), {
		method: 'GET',
	});

	const defaultData = {
		title: _x(
			'Time limit expired',
			'Seat selection expired timer title',
			'event-tickets'
		),
		content: _x(
			'Your seat selections are no longer reserved, but tickets are still available.',
			'Seat selection expired timer content',
			'event-tickets'
		),
		buttonLabel: _x(
			'Find Seats',
			'Seat selection expired timer button label',
			'event-tickets'
		),
		redirectUrl: '/',
	};

	if (!response.ok) {
		console.error('Failed to fetch interrupt modal data');
		return defaultData;
	}

	const responseJson = await response.json();

	if (!(responseJson.success && responseJson.data)) {
		console.error('Failed to fetch interrupt modal data');
		return defaultData;
	}

	return {
		title: responseJson.data.title || defaultData.title,
		content: responseJson.data.content || defaultData.content,
		buttonLabel: responseJson.data.buttonLabel || defaultData.buttonLabel,
		redirectUrl: responseJson.data.redirectUrl || defaultData.redirectUrl,
	};
}

/**
 * Returns  the interrupt dialog element.
 *
 * @since TBD
 *
 * @return {A11yDialog|null} Either the interrupt dialog element or `null` if it could not be found.
 */
async function getInterruptDialogElement() {
	const firstTimerElement = getTimerElements()?.[0];

	if (!firstTimerElement) {
		console.warn('No timer element found');
		return null;
	}

	const dialogDataJSAttribute =
		'dialog-content-tec-tickets-seating-timer-interrupt';

	const { title, content, buttonLabel, redirectUrl } =
		await fetchInterruptModalData();

	// The `A11yDialog` library will read this data attribute to find the dialog element..
	firstTimerElement.dataset.content = dialogDataJSAttribute;

	// By default, attach the dialog to the first timer element.
	let appendTarget = '.tec-tickets-seating__timer';

	// Are we rendering inside another dialog?
	const dialogParent = firstTimerElement.closest('.tribe-dialog');

	if (dialogParent) {
		// If the timer element is being rendered in the context of a dialog, then attach the dialog to that dialog parent.
		appendTarget =
			'.tribe-tickets__tickets-form, .tec-tickets-seating__tickets-block';
	}

	if (!interruptDialogElement) {
		interruptDialogElement = InterruptDialogComponent({
			dataJs: dialogDataJSAttribute,
			title,
			content,
			buttonLabel,
			redirectUrl,
		});

		document
			.querySelector(appendTarget)
			?.appendChild(interruptDialogElement);
	}

	// @see tec-a11y-dialog.js in Common.
	return new A11yDialog({
		trigger: '.tec-tickets-seating__timer',
		appendTarget,
		wrapperClasses: 'tribe-dialog',
		overlayClasses: 'tribe-dialog__overlay tribe-modal__overlay',
		contentClasses:
			'tribe-dialog__wrapper tribe-tickets-seating__interrupt-wrapper',
		overlayClickCloses: false,
	});
}

/**
 * Interrupts the user triggering the user flow redirection when the time is up.
 *
 * @since TBD
 *
 * @return {void} The timer is interrupted.
 */
async function interrupt() {
	getTimerElements().forEach((timerElement) => {
		setTimerTimeLeft(timerElement, 0, 0);
	});

	clearTimeout(countdownLoopId);
	clearTimeout(healthCheckLoopId);
	const interruptDialog = await getInterruptDialogElement();

	/**
	 * Fires to trigger an interruption of the user flow due to the timer expiring.
	 *
	 * @since TBD
	 */
	doAction('tec.tickets.seating.timer_interrupt');

	if (interruptDialog) {
		interruptDialog.show();
		// This is a  hack to prevent the user from being able to dismiss or close the dialog.
		interruptDialog.shown = false;
	}
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

		return;
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
	}, 3 * 1000);
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
	if (!healthCheckLoopId) {
		startHealthCheckLoop();
	}
}

/**
 * Sends a request to the backend to either start or sync the timer.
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
	const { postId, token } = findTimerData();

	if (!(secondsLeft && postId && token)) {
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
 * Postpones the healthcheck that will sync with the backend resetting its timer.
 *
 * @since TBD
 *
 * @return {void}
 */
export function postponeHealthcheck() {
	if (healthCheckLoopId) {
		clearTimeout(healthCheckLoopId);
	}

	// Postpone the healthcheck for 5 seconds.
	setTimeout(syncWithBackend, 5000);
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

/**
 * Watches for the checkout controls to be clicked or submitted and postpones the healthcheck.
 *
 * @since TBD
 *
 * @param {HTMLElement|null} parent The parent element to search the checkout controls in.
 *
 * @return {void}
 */
export function watchCheckoutControls(parent) {
	/**
	 * Filters the selectors used to find the checkout controls on the page.
	 *
	 * @since TBD
	 *
	 * @type {string} The `querySeelctorAll` selectors used to find the checkout controls on the page.
	 */
	const filteredCheckoutControls = applyFilters(
		'tec.tickets.seating.frontend.session.checkoutControls',
		checkoutControlsSelectors
	);

	parent = parent || document;

	const checkoutControlElements = parent.querySelectorAll(
		filteredCheckoutControls
	);

	checkoutControlElements.forEach((checkoutControlElement) => {
		checkoutControlElement.addEventListener('click', postponeHealthcheck);
		checkoutControlElement.addEventListener('submit', postponeHealthcheck);
	});
}

/**
 * Sets the healthcheck loop ID.
 *
 * @since TBD
 *
 * @param {number} id The ID of the healthcheck loop.
 *
 * @return {number} The updated healthcheck loop ID.
 */
export function setHealthcheckLoopId(id) {
	healthCheckLoopId = id;

	return healthCheckLoopId;
}

// On DOM ready check if any timer needs to be synced.
onReady(syncOnLoad);
onReady(watchCheckoutControls);

window.tec = window.tec || {};
window.tec.tickets = window.tec.tickets || {};
window.tec.tickets.seating = window.tec.tickets.seating || {};
window.tec.tickets.seating.frontend = window.tec.tickets.seating.frontend || {};
window.tec.tickets.seating.frontend.session = {
	...(window.tec.tickets.seating.frontend.session || {}),
	start,
	reset,
	syncWithBackend,
	interrupt,
};
