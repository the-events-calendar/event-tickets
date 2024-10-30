/**
 * @typedef {Object} SeatMapTicketEntry
 * @property {string}               ticketId              The ticket ID.
 * @property {string}               name                  The ticket name.
 * @property {number}               price                 The ticket price.
 * @property {string}               description           The ticket description.
 *
 * @typedef {Object} SeatTypeMap
 * @property {string}               id                    The seat type ID.
 * @property {SeatMapTicketEntry[]} tickets               The list of tickets for the seat type.
 *
 * @typedef {Object} SeatsReportLocalizedData
 * @property {SeatTypeMap}          seatTypeMap           The map of seat types.
 * @property {string}               postId                The post ID of the post to purchase tickets for.
 * @property {string}               fetchAttendeesAjaxUrl The AJAX URL to fetch the attendees for the post.
 *
 * @type {SeatsReportLocalizedData}
 */
export const localizedData =
	window?.tec?.tickets?.seating?.admin?.seatsReport;
