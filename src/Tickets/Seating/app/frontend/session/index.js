import { localizedData } from './localized-data';
import './style.pcss';
import { doAction, applyFilters } from '@wordpress/hooks';
import { InterruptDialogComponent } from './interrupt-dialog-component';
import { _x } from '@wordpress/i18n';
import { onReady } from '@tec/tickets/seating/utils';

const {
	ajaxUrl,
	ajaxNonce,
	checkoutGraceTime,
	ACTION_START,
	ACTION_SYNC,
	ACTION_INTERRUPT_GET_DATA,
	ACTION_PAUSE_TO_CHECKOUT,
} = localizedData;

/**
 * The selector used to find the timer elements on the page.
 *
 * @since 5.16.0
 *
 * @type {string}
 */
const selector = '.tec-tickets-seating__timer';

/**
 * The class name that, applied to the timer elements, will hide them.
 *
 * @since 5.16.0
 *
 * @type {string}
 */
const hiddenClassName = 'tec-tickets-seating__timer--hidden';

/**
 * The ID of the countdown loop that will update the timer every second.
 *
 * @since 5.16.0
 *
 * @type {?number}
 */
let countdownTimeoutId = null;

/**
 * The ID of the health check loop that will sync the timer with the backend every minute.
 *
 * @since 5.16.0
 *
 * @type {?number}
 */
let healthCheckTimeoutId = null;

/**
 * The ID of the resume loop that will resume the timer after a pause.
 *
 * @since 5.16.0
 *
 * @type {?number}
 */
let resumeTimeoutId = null;

/**
 * Whether the timer has been started or not.
 *
 * @since 5.16.0
 *
 * @type {boolean}
 */
let started = false;

/**
 * Whether the timer is currently interruptable or not.
 *
 * @since 5.16.0
 *
 * @type {boolean}
 */
let interruptable = true;

/**
 * Whether the timer has expired or not.
 *
 * @since 5.16.0
 *
 * @type {boolean}
 */
let expired = false;

/**
 * The interrupt dialog HTML element.
 *
 * @since 5.16.0
 *
 * @type {HTMLElement|null}
 */
let interruptDialogElement = null;

/**
 * The document element that should be targeted by the module.
 * Defaults to the document.
 *
 * @since 5.16.0
 *
 * @type {HTMLElement}
 */
let targetDom = document;

/**
 * The list of checkout controls that are being watched.
 *
 * @since 5.16.0
 *
 *
 * @type {HTMLElement[]} the list of checkout controls that are being watched.
 */
let watchedCheckoutControls = [];

/**
 * The selectors used to find the checkout controls on the page.
 *
 * @since 5.16.0
 *
 * @type {string[]}
 */
export const checkoutControlsSelectors = [
	'.tribe-tickets__commerce-checkout-form-submit-button',
	'.tribe-tickets__commerce-checkout-paypal-buttons button',
];

/**
 * Sets the interruptable flag.
 *
 * @since 5.16.0
 *
 * @param {boolean} interruptableFlag The interruptable flag.
 */
export function setIsInterruptable(interruptableFlag) {
	interruptable = interruptableFlag;
}

/**
 * Returns the interruptable flag.
 *
 * @since 5.16.0
 *
 * @return {boolean} Whether the timer is currently interruptable or not.
 */
export function isInterruptable() {
	return interruptable;
}

/**
 * Sets the expired flag.
 *
 * @since 5.16.0
 *
 * @param {boolean} expiredFlag The expired flag.
 */
function setIsExpired(expiredFlag) {
	expired = expiredFlag;
}

/**
 * Returns the expired flag.
 *
 * @since 5.16.0
 *
 * @return {boolean} Whether the timer has expired or not.
 */
export function isExpired() {
	return expired;
}

/**
 * Sets the started flag.
 *
 * @since 5.16.0
 *
 * @param {boolean} startedFlag The started flag.
 */
function setIsStarted(startedFlag) {
	started = startedFlag;
}

/**
 * Returns the started flag.
 *
 * @since 5.16.0
 *
 * @return {boolean} Whether the timer has been started or not.
 */
export function isStarted() {
	return started;
}

/**
 * @typedef {Object} TimerData
 * @property {number} postId      The post ID of the post to purchase tickets for.
 * @property {string} token       The ephemeral token used to secure the iframe communication with the service.
 * @property {string} redirectUrl The URL to redirect the user to when the timer expires.
 */

/**
 * Returns all the timer elements on the page.
 *
 * @since 5.16.0
 *
 * @return {NodeList<HTMLElement>} All the timer elements on the page.
 */
function getTimerElements() {
	return targetDom.querySelectorAll(selector);
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
 * @since 5.16.0
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
 * @since 5.16.0
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
		redirectUrl: window.location.href,
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
 * @since 5.16.0
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

		targetDom
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
 * @since 5.16.0
 *
 * @return {void} The timer is interrupted.
 */
async function interrupt() {
	if ( ! isInterruptable() ) {
		return true;
	}

	setIsInterruptable(true);

	getTimerElements().forEach((timerElement) => {
		setTimerTimeLeft(timerElement, 0, 0);
	});

	setIsExpired(true);
	clearTimeout(countdownTimeoutId);
	countdownTimeoutId = null;
	clearTimeout(healthCheckTimeoutId);
	healthCheckTimeoutId = null;
	const interruptDialog = await getInterruptDialogElement();

	/**
	 * Fires to trigger an interruption of the user flow due to the timer expiring.
	 *
	 * @since 5.16.0
	 */
	doAction('tec.tickets.seating.timer_interrupt');

	if (interruptDialog) {
		interruptDialog.show();
		// This is a  hack to prevent the user from being able to dismiss or close the dialog.
		interruptDialog.shown = false;
	}

	setIsInterruptable(false);
}

/**
 * Sends a beacon to the backend to interrupt the user flow.
 *
 * @since 5.16.0
 *
 * @return {void} The timer is interrupted.
 */
export function beaconInterrupt() {
	if (!isInterruptable()) {
		return;
	}

	const { postId, token } = findTimerData();
	const requestUrl = new URL(ajaxUrl);
	requestUrl.searchParams.set('_ajaxNonce', ajaxNonce);
	requestUrl.searchParams.set('action', ACTION_INTERRUPT_GET_DATA);
	requestUrl.searchParams.set('postId', postId);
	requestUrl.searchParams.set('token', token);

	window.navigator.sendBeacon(requestUrl.toString());
}

/**
 * Starts the loop that will recursively update the timer(s) every second.
 *
 * @since 5.16.0
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

	setIsStarted(true);

	countdownTimeoutId = setTimeout(() => {
		secondsLeft -= 1;
		getTimerElements().forEach((timerElement) => {
			setTimerTimeLeft(
				timerElement,
				Math.floor(secondsLeft / 60),
				secondsLeft % 60
			);
		});

		if (!isExpired()) {
			startCountdownLoop(secondsLeft);
		}
	}, 1000);
}

/**
 * Starts a loop to sync the timer with the backend every minute.
 *
 * @since 5.16.0
 *
 * @return {void}
 */
function startHealthCheckLoop() {
	if ( isExpired() ) {
		return;
	}

	healthCheckTimeoutId = setTimeout(async () => {
		await syncWithBackend();
		startHealthCheckLoop();
	}, 3 * 1000);
}

/**
 * Sends a request to the backend to get the timer's seconds left.
 *
 * If the seconds left is less than or equal to 0, the interruption logic will be triggered.
 *
 * @since 5.16.0
 *
 * @return {Promise<void>} A promise that will resolve when the request is completed.
 */
export async function syncWithBackend() {
	if ( isExpired() || getTimerElements().length === 0 ) {
		return;
	}

	const secondsLeft = await requestToBackend(ACTION_SYNC);

	if (secondsLeft <= 0) {
		interrupt();
		return;
	}

	if (countdownTimeoutId) {
		clearTimeout(countdownTimeoutId);
		countdownTimeoutId = null;
	}

	startCountdownLoop(secondsLeft);
	if (!healthCheckTimeoutId) {
		startHealthCheckLoop();
	}
}

/**
 * Sends a request to the backend to either start or sync the timer.
 *
 * @since 5.16.0
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

	if (
		[ACTION_START, ACTION_SYNC, ACTION_PAUSE_TO_CHECKOUT].indexOf(
			action
		) === -1
	) {
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
 * @since 5.16.0
 *
 * @return {Promise<void>} A Promise that resolves when the timer is started.
 */
export async function start() {
	if (setIsStarted() || getTimerElements().length === 0) {
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

	setIsStarted(true);
	startCountdownLoop(secondsLeft);
	startHealthCheckLoop();
}

/**
 * Resets the timer cancelling any pending countdown and health check loops and setting the started flag to false.
 *
 * @since 5.16.0
 *
 * @return {void} The timer is reset.
 */
export function reset() {
	if (countdownTimeoutId) {
		clearTimeout(countdownTimeoutId);
	}

	if (healthCheckTimeoutId) {
		clearTimeout(healthCheckTimeoutId);
	}

	if (resumeTimeoutId) {
		clearTimeout(resumeTimeoutId);
	}

	started = false;
	expired = false;
	healthCheckTimeoutId = null;
	countdownTimeoutId = null;
	resumeTimeoutId = null;
	interruptable = true;
	stopWatchingCheckoutControls();
}

/**
 * Postpones the healthcheck that will sync with the backend resetting its timer.
 *
 * @since 5.16.0
 * @since 5.17.0 Added the `resumeInSeconds` parameter.
 *
 * @param {number} resumeInSeconds The amount of seconds after which the timer should resume. `0` to not resume.
 *
 * @return {void}
 */
export function pause(resumeInSeconds) {
	// By default, do not resume.
	resumeInSeconds = resumeInSeconds || 0;

	setIsInterruptable(false);

	if (healthCheckTimeoutId) {
		// Pause the healthcheck loop.
		clearTimeout(healthCheckTimeoutId);
		healthCheckTimeoutId = null;
	}

	if (countdownTimeoutId) {
		// Pause the countdown loop.
		clearTimeout(countdownTimeoutId);
		countdownTimeoutId = null;
	}

	if (!resumeInSeconds) {
		return;
	}

	// Postpone the healthcheck for 60 seconds.
	resumeTimeoutId = setTimeout(resume, resumeInSeconds * 1000);
}

/**
 * Postpones the healthcheck that will sync with the backend resetting its timer for the purpose of
 * giving the user time to checkout.
 *
 * @since 5.17.0
 *
 * @return {Promise<void>} A promise that will resolve when the backend received the signal and the timer paused.
 */
export async function pauseToCheckout() {
	const secondsLeft = await requestToBackend(ACTION_PAUSE_TO_CHECKOUT);

	if (secondsLeft <= 0) {
		interrupt();
		return;
	}

	pause(checkoutGraceTime);
}

/**
 * Resumes the timer from a pause.
 *
 * @since 5.16.0
 *
 * @return {void} The timer is resumed.
 */
export async function resume() {
	if (resumeTimeoutId) {
		clearTimeout(resumeTimeoutId);
		resumeTimeoutId = null;
	}

	setIsInterruptable(true);
	await syncWithBackend();
}

/**
 * Sets the DOM to initialize the timer(s) in.
 *
 * Defaults to the document.
 *
 * @since 5.16.0
 *
 * @param {HTMLElement} targetDocument The DOM to initialize the timer(s) in.
 */
export function setTargetDom(targetDocument) {
	targetDom = targetDocument || document;
}

/**
 * Syncs the timer with the backend on DOM ready.
 *
 * @since 5.16.0
 *
 * @return {void} The timer is synced.
 */
export async function syncOnLoad() {
	const syncTimerElements = Array.from(getTimerElements()).filter(
		(syncTimerElement) => {
			return 'syncOnLoad' in syncTimerElement.dataset;
		}
	);

	if (syncTimerElements.length === 0) {
		return;
	}

	setIsInterruptable(true);

	// On page/tab close (or app close in some instances) interrupt the timer, clear the sessions and cancel the reservations.
	window.addEventListener('beforeunload', beaconInterrupt);

	await syncWithBackend();
}

/**
 * Watches for the checkout controls to be clicked or submitted and postpones the healthcheck.
 *
 * @since 5.16.0
 *
 * @return {void}
 */
export function watchCheckoutControls() {
	/**
	 * Filters the selectors used to find the checkout controls on the page.
	 *
	 * @since 5.16.0
	 *
	 * @type {string[]} The `querySelectorAll` selectors used to find the checkout controls on the page.
	 */
	const filteredCheckoutControls = applyFilters(
		'tec.tickets.seating.frontend.session.checkoutControls',
		checkoutControlsSelectors
	);

	const checkoutControlElements = targetDom.querySelectorAll(
		filteredCheckoutControls.join(', ')
	);

	checkoutControlElements.forEach((checkoutControlElement) => {
		watchedCheckoutControls.push(checkoutControlElement);
		checkoutControlElement.addEventListener('click', pauseToCheckout);
		checkoutControlElement.addEventListener('submit', pauseToCheckout);
	});
}

/**
 * Remove the event listeners from the watched checkout controls.
 *
 * @since 5.16.0
 *
 * @return {void} The event listeners are removed from the watched checkout controls.
 */
function stopWatchingCheckoutControls() {
	watchedCheckoutControls.forEach((checkoutControlElement) => {
		checkoutControlElement.removeEventListener('click', pauseToCheckout);
		checkoutControlElement.removeEventListener('submit', pauseToCheckout);
	});

	watchedCheckoutControls = [];
}

/**
 * Returns the list of checkout controls that are being watched by the timer.
 *
 * @since 5.16.0
 *
 * @return {HTMLElement[]} The list of checkout controls that are being watched by the timer.
 */
export function getWatchedCheckoutControls() {
	return watchedCheckoutControls;
}

/**
 * Sets the healthcheck loop ID.
 *
 * @since 5.16.0
 *
 * @param {number} id The ID of the healthcheck loop.
 *
 * @return {number} The updated healthcheck loop ID.
 */
export function setHealthcheckLoopId(id) {
	healthCheckTimeoutId = id;

	return healthCheckTimeoutId;
}

/**
 * Returns the ID of the healthcheck timeout.
 *
 * @since 5.16.0
 *
 * @return {?number} The ID of the healthcheck timeout.
 */
export function getHealthcheckTimeoutId() {
	return healthCheckTimeoutId;
}

/**
 * Returns the ID of the countdown timeout.
 *
 * @since 5.16.0
 *
 * @return {?number} The ID of the countdown timeout.
 */
export function getCountdownTimeoutId() {
	return countdownTimeoutId;
}

/**
 * Returns the ID of the resume timeout
 *
 * @since 5.16.0
 *
 * @return {?number} The ID of the resume timeout.
 */
export function getResumeTimeoutId() {
	return resumeTimeoutId;
}

// On DOM ready check if any timer needs to be synced.
onReady(() => syncOnLoad());
onReady(() => watchCheckoutControls());

window.tec = window.tec || {};
window.tec.tickets = window.tec.tickets || {};
window.tec.tickets.seating = window.tec.tickets.seating || {};
window.tec.tickets.seating.frontend = window.tec.tickets.seating.frontend || {};
window.tec.tickets.seating.frontend.session = {
	...(window.tec.tickets.seating.frontend.session || {}),
	start,
	reset,
	syncOnLoad,
	interrupt,
	setIsInterruptable,
};
