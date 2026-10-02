/**
 * Reads the data the server localizes the Ticket block script with.
 *
 * @since TBD
 */

/** @typedef {import( '../sale-window' ).SaleWindowEnd} SaleWindowEnd */
/** @typedef {import( '../sale-price-window' ).SalePriceBoundary} SalePriceBoundary */

/**
 * @typedef {Object} HelperTextFormats
 *
 * @property {string} dateWithYear The date format of a date in another year, in PHP date format.
 * @property {string} dateNoYear   The date format of a date in the current year, in PHP date format.
 * @property {string} time         The site time format, in PHP date format.
 */

/**
 * @typedef {Object} BlockEditorData
 *
 * @property {Object<string,SaleWindowEnd>}     defaults          The relative `start` and `end` each end of the window
 *                                                                offers when the rule has none.
 * @property {Object<string,SalePriceBoundary>} salePriceDefaults The `start` and `end` of the sale price window, and the
 *                                                                relative values each offers, when the sale price has
 *                                                                no rule.
 * @property {Object<string,string>}            timezones         The zone the server resolves each manual UTC offset
 *                                                                option to.
 * @property {Object}                           allDay            The times the server saves for an all-day event:
 *                                                                `start` and `end`, `HH:mm:ss`, and `endDays`.
 * @property {HelperTextFormats}                formats           The date and time formats of the helper text.
 */

/**
 * Gets the data the server localizes the Ticket block script with.
 *
 * @since TBD
 *
 * @return {BlockEditorData} The localized data.
 */
export function getLocalizedData() {
	return window.tec.tickets.relativeSaleDates.blockEditorData;
}
