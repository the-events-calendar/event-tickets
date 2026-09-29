/**
 * Picks the date format of a helper text date, as the server formats dates.
 *
 * @since TBD
 */

/** @typedef {import( 'moment' ).Moment} Moment */

/**
 * Picks the date format of a helper text date: without the year for a date in the current year, with it otherwise.
 *
 * @since TBD
 *
 * @param {Moment} date                 The sales date.
 * @param {Object} formats              The date formats, in PHP date format.
 * @param {string} formats.dateWithYear The format of a date in another year.
 * @param {string} formats.dateNoYear   The format of a date in the current year.
 * @param {number} currentYear          The current year.
 *
 * @return {string} The date format.
 */
export function getHelperDateFormat( date, { dateWithYear, dateNoYear }, currentYear ) {
	return date.year() === currentYear ? dateNoYear : dateWithYear;
}
