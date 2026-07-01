import { globals } from '@moderntribe/common/utils';

const PHP_TO_MOMENT_FORMAT = {
	d: 'DD',
	D: 'ddd',
	j: 'D',
	l: 'dddd',
	N: 'E',
	S: 'o',
	w: 'e',
	z: 'DDD',
	W: 'W',
	F: 'MMMM',
	m: 'MM',
	M: 'MMM',
	n: 'M',
	t: '',
	L: '',
	o: 'YYYY',
	Y: 'YYYY',
	y: 'YY',
	a: 'a',
	A: 'A',
	B: '',
	g: 'h',
	G: 'H',
	h: 'hh',
	H: 'HH',
	i: 'mm',
	s: 'ss',
	u: 'SSS',
	e: 'zz',
	I: '',
	O: '',
	P: '',
	T: '',
	Z: '',
	c: '',
	r: '',
	U: 'X',
};

const convertFormat = ( format ) => {
	const chars = format.split( '' );
	return chars.map( ( c ) => ( c in PHP_TO_MOMENT_FORMAT ? PHP_TO_MOMENT_FORMAT[ c ] : c ) ).join( '' );
};

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
	const dateFormat = convertFormat( phpFormat );

	const startFormatted = startDateMoment.format( dateFormat );
	const endFormatted = endDateMoment.format( dateFormat );

	return `${ startFormatted } - ${ endFormatted }`;
};
