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

	const phpFormat = globals.tecDateSettings().datepickerFormat || 'F j, Y';
	const dateFormat = momentUtil.toFormat( phpFormat );

	const startFormatted = startDateMoment.format( dateFormat );
	const endFormatted = endDateMoment.format( dateFormat );

	return `${ startFormatted } - ${ endFormatted }`;
};
