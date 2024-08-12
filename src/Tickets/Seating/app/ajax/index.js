import { localizedData } from './localized-data';

const {
	ajaxUrl,
	ajaxNonce,
	ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID,
	ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE,
	ACTION_INVALIDATE_LAYOUTS_CACHE,
	ACTION_DELETE_MAP,
	ACTION_DELETE_LAYOUT,
	ACTION_POST_RESERVATIONS,
	ACTION_CLEAR_RESERVATIONS,
	ACTION_FETCH_ATTENDEES,
	ACTION_DELETE_RESERVATIONS,
	ACTION_SEAT_TYPES_UPDATED,
	ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES,
	ACTION_RESERVATION_CREATED,
	ACTION_RESERVATION_UPDATED,
} = localizedData;

export {
	ajaxUrl,
	ajaxNonce,
	ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID,
	ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE,
	ACTION_INVALIDATE_LAYOUTS_CACHE,
	ACTION_DELETE_MAP,
	ACTION_DELETE_LAYOUT,
	ACTION_POST_RESERVATIONS,
	ACTION_CLEAR_RESERVATIONS,
	ACTION_FETCH_ATTENDEES,
	ACTION_DELETE_RESERVATIONS,
	ACTION_SEAT_TYPES_UPDATED,
	ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES,
	ACTION_RESERVATION_CREATED,
	ACTION_RESERVATION_UPDATED,
};

window.tec = window.tec || {};
window.tec.tickets.seating = window.tec.tickets.seating || {};
window.tec.tickets.seating.ajax = window.tec.tickets.seating.ajax || {};
window.tec.tickets.seating.ajax = {
	...window.tec.tickets.seating.ajax,
	ajaxUrl,
	ajaxNonce,
	ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID,
	ACTION_INVALIDATE_MAPS_LAYOUTS_CACHE,
	ACTION_INVALIDATE_LAYOUTS_CACHE,
	ACTION_DELETE_MAP,
	ACTION_DELETE_LAYOUT,
	ACTION_POST_RESERVATIONS,
	ACTION_CLEAR_RESERVATIONS,
	ACTION_FETCH_ATTENDEES,
	ACTION_DELETE_RESERVATIONS,
	ACTION_SEAT_TYPES_UPDATED,
	ACTION_RESERVATIONS_UPDATED_FROM_SEAT_TYPES,
	ACTION_RESERVATION_CREATED,
	ACTION_RESERVATION_UPDATED,
};
