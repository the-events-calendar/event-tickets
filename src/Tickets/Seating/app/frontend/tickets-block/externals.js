/**
 * @typedef {Object} SeatMapTicketEntry
 * @property {string}                ticketId      The ticket ID.
 * @property {string}                name          The ticket name.
 * @property {number}                price         The ticket price.
 * @property {string}                description   The ticket description.
 *
 * @typedef {Object} SeatTypeMap
 * @property {string}                id            The seat type ID.
 * @property {SeatMapTicketEntry[]}  tickets       The list of tickets for the seat type.
 *
 * @typedef {Object} TicketBlockExternals
 * @property {string}                objectName    The key to fetch the modal dialog from the window object.
 * @property {SeatTypeMap[]}         seatTypeMap   The map of seat types
 * @property {Object<string,string>} labels        The labels for the seat types.
 * @property {string}                providerClass The provider class.
 * @property {number}                postId        The post ID.
 */

/**
 *
 * @type {TicketBlockExternals}
 */
export const externals = window?.tec?.tickets?.seating?.frontend?.ticketsBlock;
