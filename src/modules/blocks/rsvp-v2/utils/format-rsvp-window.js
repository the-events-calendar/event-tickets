import { globals, moment as momentUtil } from '@moderntribe/common/utils';

/**
 * Format RSVP start and end dates for display in the RSVP Window section.
 *
 * @param {Object} startDateMoment Moment object for the start date.
 * @param {Object} endDateMoment   Moment object for the end date.
 * @return {string} Formatted date range string, or empty string when dates are missing.
 */
export const formatRsvpWindow = ( startDateMoment, endDateMoment ) => {
	if ( ! startDateMoment?.isValid?.() || ! endDateMoment?.isValid?.() ) {
		return '';
	}

	const dateFormat = globals.tecDateSettings().datepickerFormat
	? momentUtil.toFormat( globals.tecDateSettings().datepickerFormat )
	: 'LL';

	const startFormatted = momentUtil.toDatePicker( startDateMoment, dateFormat );
	const endFormatted = momentUtil.toDatePicker( endDateMoment, dateFormat );

	return `${ startFormatted } - ${ endFormatted }`;
};
