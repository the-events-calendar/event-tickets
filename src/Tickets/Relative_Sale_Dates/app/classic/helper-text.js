/**
 * Writes the text that tells the admin when a relative sales window works out to.
 *
 * @since TBD
 */

/** @typedef {import( 'moment' ).Moment} Moment */

/**
 * @typedef {Object} HelperTextSettings
 *
 * @property {string} dateWithYear The date format of a date in another year, in PHP date format.
 * @property {string} dateNoYear   The date format of a date in the current year, in PHP date format.
 * @property {string} timeFormat   The site time format, in PHP date format.
 * @property {Object} dateSettings The translated day and month names `DateFormatter` formats with.
 */

/**
 * Fills a helper text template with a sales date.
 *
 * The year is left out of a date in the current year, as the server formats dates.
 *
 * @since TBD
 *
 * @param {string}             template    The text, with `%1$s` for the date and `%2$s` for the time.
 * @param {Moment}             date        The sales date, in the event timezone.
 * @param {HelperTextSettings} settings    The date and time formats.
 * @param {number}             currentYear The current year.
 *
 * @return {string} The helper text.
 */
export function formatHelperText( template, date, settings, currentYear ) {
	const formatter = new window.DateFormatter( { dateSettings: settings.dateSettings } );
	// `DateFormatter` reads a date in the browser's timezone, so it gets the event's wall-clock time.
	const wallClock = new Date( date.year(), date.month(), date.date(), date.hours(), date.minutes(), date.seconds() );
	const dateFormat = date.year() === currentYear ? settings.dateNoYear : settings.dateWithYear;

	return template
		.replace( '%1$s', formatter.formatDate( wallClock, dateFormat ) )
		.replace( '%2$s', formatter.formatDate( wallClock, settings.timeFormat ) );
}
