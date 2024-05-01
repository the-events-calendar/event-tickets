/**
 * Gets a reference to the notice element from the DOM.
 *
 * @since TBD
 *
 * @returns {Element|null} The notice element, or null if it does not exist.j
 */
function getNoticeElement() {
	return document.getElementById('tec-events-assigned-seating-notice');
}

/**
 * Hides the notice element.
 *
 * @since TBD
 *
 * @param {Element|null} notice The notice element to hide.
 */
function hideNotice(notice) {
	if (!notice) {
		return;
	}
	notice.style.display = 'none';
	notice.style.visibility = 'hidden';
}

/**
 * Shows the notice element.
 *
 * @since TBD
 *
 * @param {Element|null} notice The notice element to show.
 */
function showNotice(notice) {
	if (!notice) {
		return;
	}
	notice.style.display = 'block';
	notice.style.visibility = 'visible';
}

/**
 * Sets the notice element to display the given class.
 *
 * @since TBD
 *
 * @param {Element|null} notice The notice element to manipulate.
 * @param {string} className The class to set; all other classes will be removed.
 */
function setNoticeClass(notice, className) {
	if (!notice) {
		return;
	}
	const classes = notice.classList;
	classes.remove('notice-success');
	classes.remove('notice-warning');
	classes.remove('notice-error');
	classes.add(className);
}

/**
 * Sets the notice element to display the given message.
 *
 * @since TBD
 *
 * @param {string} message The message to display.
 */
function setNoticeMessage(notice, message) {
	notice.innerHTML = '<p>' + message + '</p>';
}

/**
 * Notifies the user of an error by manipulating the notice element.
 *
 * @since TBD
 *
 * @param {string} message The message to display.
 */
function notifyUserOfError(message) {
	const notice = getNoticeElement();
	hideNotice(notice);
	setNoticeClass(notice, 'notice-error');
	setNoticeMessage(notice, message);
	showNotice(notice);
}

/**
 * Notifies the user of a warning by manipulating the notice element.
 *
 * @since TBD
 *
 * @param {string} message The message to display.
 */
function notifyUserOfWarning(message) {
	const notice = getNoticeElement();
	hideNotice(notice);
	setNoticeClass(notice, 'notice-warning');
	setNoticeMessage(notice, message);
	showNotice(notice);
}

window.tec = window.tec || {};
window.tec.eventsAssignedSeating = window.tec.eventsAssignedSeating || {};
window.tec.eventsAssignedSeating.notices = {
	...(window.tec.eventsAssignedSeating.notices || {}),
	notifyUserOfWarning,
	notifyUserOfError,
};