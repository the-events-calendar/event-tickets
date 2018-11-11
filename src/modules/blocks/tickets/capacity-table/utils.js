import identity from 'lodash/identity';

/**
 * Create a series of strings into a single string with parenthesis around it, making sure removes
 * any empty string as well
 *
 * @param {array} items an array of strings
 * @returns {string} return a new label string if items has any valid string
 */
export const toLabel = ( items = [] ) => {
	const label = items.filter( identity ).join( ', ' );
	return label.length ? ` ( ${ label } ) ` : '';
}
