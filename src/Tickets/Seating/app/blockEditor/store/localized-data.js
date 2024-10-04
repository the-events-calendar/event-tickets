/**
 * @typedef {'down'|'not-connected'|'invalid-license'} StatusString
 */

/**
 * @typedef {Object} ServiceStatus
 * @property {boolean}      ok         Whether the service is up and running or not.
 * @property {StatusString} status     The service status message.
 * @property {string}       connectUrl The URL to connect to the service.
 */

/**
 * @typedef {Object} StoreLocalizedData
 * @property {boolean}                                          isUsingAssignedSeating Whether the post is using assigned seating or not.
 * @property {Array<{id: string, name: string, seats: number}>} layouts                The layouts in option format.
 * @property {Array<{id: string, name: string, seats: number}>} seatTypes              The seat types in option format.
 * @property {string|null}                                      currentLayoutId        The current layout ID.
 * @property {Map<number,string>}                               seatTypesByPostId      A map of seat types by post ID.
 * @property {boolean}                                          isLayoutLocked         Whether the layout is locked or not.
 * @property {number|null}                                      eventCapacity          The event capacity.
 * @property {ServiceStatus}                                    serviceStatus          The service status.
 */

/**
 * @type {StoreLocalizedData}
 */
export const localizedData = window?.tec?.tickets?.seating?.blockEditor;
