/**
 * The sale price rule the Ticket block's sale price window options show.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import { MODE_SPECIFIC } from '../rule-constants';
import { getLocalizedData } from './localized-data';

/** @typedef {import( '../sale-price-window' ).SalePriceRule} SalePriceRule */

/**
 * Builds the sale price rule the options show for a ticket, with relative values on every boundary so a switch to a
 * relative mode finds them.
 *
 * @since TBD
 *
 * @param {SalePriceRule|null|undefined} rule The ticket's draft sale price rule: `undefined` for a ticket without a
 *                                            sale price, `null` for a sale price saved without a rule.
 *
 * @return {SalePriceRule} The sale price rule the options show.
 */
export function getFormSalePriceRule( rule ) {
	const { salePriceDefaults: defaults } = getLocalizedData();

	if ( undefined === rule ) {
		return { start: { ...defaults.start }, end: { ...defaults.end } };
	}

	// A sale price saved without a rule keeps the dates it was saved with, as a specific window.
	if ( null === rule ) {
		return { start: { ...defaults.start, mode: MODE_SPECIFIC }, end: { ...defaults.end, mode: MODE_SPECIFIC } };
	}

	// A stored boundary that is not relative has only its mode; a draft one keeps the values the admin left.
	return { start: { ...defaults.start, ...rule.start }, end: { ...defaults.end, ...rule.end } };
}
