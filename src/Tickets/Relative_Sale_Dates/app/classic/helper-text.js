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
	const wallClock = toWallClockDate( date );
	const dateFormat = date.year() === currentYear ? settings.dateNoYear : settings.dateWithYear;

	return template
		.replace( '%1$s', formatter.formatDate( wallClock, dateFormat ) )
		.replace( '%2$s', formatter.formatDate( wallClock, settings.timeFormat ) );
}

/**
 * Builds the `Date` `DateFormatter` reads the event's wall-clock time from.
 *
 * `DateFormatter` reads a date through its local getters. A `Date` built from the wall-clock time in the browser's own
 * timezone moves a time that timezone's clocks skip, so the date is built in UTC, which skips none, and its local
 * getters read the UTC ones.
 *
 * @since TBD
 *
 * @param {Moment} date The date, in the event timezone.
 *
 * @return {Date} A date whose local getters give the event's wall-clock date and time.
 */
function toWallClockDate( date ) {
	const wallClock = new Date(
		Date.UTC( date.year(), date.month(), date.date(), date.hours(), date.minutes(), date.seconds() )
	);

	[ 'FullYear', 'Month', 'Date', 'Day', 'Hours', 'Minutes', 'Seconds', 'Milliseconds' ].forEach( ( part ) => {
		wallClock[ `get${ part }` ] = () => wallClock[ `getUTC${ part }` ]();
	} );
	wallClock.getTimezoneOffset = () => -date.utcOffset();

	return wallClock;
}
