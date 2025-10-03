export const saleDateFormat = 'Y-m-d';

/**
 * Format a date as YYYY-MM-DD for sale date fields.
 *
 * @since TBD
 *
 * @param {Date} date The date to format.
 * @returns {string} Formatted date string.
 */
export const formatSaleDate = ( date: Date ): string => {
	const month = ( date.getMonth() + 1 ).toString().padStart( 2, '0' );
	const day = date.getDate().toString().padStart( 2, '0' );
	const year = date.getFullYear();

	return `${ year }-${ month }-${ day }`;
};
