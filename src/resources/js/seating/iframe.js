const {_x} = wp.i18n;
const {notifyUserOfError} = tec.eventsAssignedSeating.notices;
const {
	establishReadiness,
	onEveryAction,
} = tec.eventsAssignedSeating.service;

function catchMessage() {
	console.log('[Maps] Message received from service', event);
}

async function init(iframe) {
	if (!iframe) {
		return;
	}

	const container = iframe.closest(
		'.tec-events-assigned-seating__iframe-container');

	if (!container) {
		return;
	}

	const token = container.dataset.token;

	if (!token) {
		const defaultMessage = _x('Ephemeral token not found in iframe element.',
			'Error message', 'events-assigned-seating');
		const error = container.dataset.error || defaultMessage;
		notifyUserOfError(error);
		return;
	}

	await establishReadiness(iframe);
	onEveryAction(iframe, catchMessage);
}

function iFrameInit() {
	const iframes = document.querySelectorAll(
		'.tec-events-assigned-seating__iframe-container iframe');

	iframes.forEach(iframe => {
		init(iframe);
	});
}

window.tec = window.tec || {};
window.tec.eventsAssignedSeating = window.tec.eventsAssignedSeating || {};
window.tec.eventsAssignedSeating.iframe = {
	...(window.tec.eventsAssignedSeating.iframe || {}),
	iFrameInit,
};