import { localizedData } from './localized-data';

// Pull in and map our data from the localized object.
const {
	nonce,
	baseUrl,
} = localizedData;

export {
	nonce,
	baseUrl,
};

// Add our data to the global `tec` object.
window.tec = window.tec || {};
window.tec.tickets = window.tec.tickets || {};
window.tec.tickets.orderModifiers = window.tec.tickets.orderModifiers || {};
window.tec.tickets.orderModifiers.rest = {
	...( window.tec.tickets.orderModifiers.rest || {} ),
	nonce,
	baseUrl,
};
