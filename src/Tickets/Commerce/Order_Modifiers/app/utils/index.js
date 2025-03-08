/**
 * Calls a callback when the DOM is ready.
 *
 * @since TBD
 *
 * @param {function} domReadyCallback The callback to call when the DOM is ready.
 *
 * @return {void}
 */
export const onReady = ( domReadyCallback ) => {
	if ( document.readyState !== 'loading' ) {
		domReadyCallback();
	} else {
		document.addEventListener( 'DOMContentLoaded', domReadyCallback );
	}
};

window.tec = window.tec || {};
window.tec.tickets.orderModifiers = window.tec.tickets.orderModifiers || {};
window.tec.tickets.orderModifiers.utils = {
	...( window.tec.tickets.orderModifiers.utils || {} ),
	onReady,
};
