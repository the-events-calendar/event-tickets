/**
 * Run a callback when the DOM is ready.
 *
 * @param {Function} domReadyCallback The callback function to be called when the DOM is ready.
 */
const onReady = (domReadyCallback) => {
	if (document.readyState !== 'loading') {
		domReadyCallback();
	} else {
		document.addEventListener('DOMContentLoaded', domReadyCallback);
	}
};

export { onReady };
