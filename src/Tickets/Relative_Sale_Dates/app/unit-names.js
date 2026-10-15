/**
 * The names of the units of a relative ticket sale date, shared by the classic and the block editor.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import { _nx } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { UNIT_DAYS, UNIT_HOURS, UNIT_MINUTES, UNIT_WEEKS } from './rule-constants';

/**
 * The name of each unit for a number of it, keyed by the unit in seconds. The msgids and context match the classic
 * template's, so one translation serves both editors.
 *
 * @since TBD
 *
 * @type {Object<number|string, function( number ): string>}
 */
export const UNIT_NAMES = {
	[ UNIT_MINUTES ]: ( number ) =>
		_nx( 'minute', 'minutes', number, 'Unit of a relative ticket sale date.', 'event-tickets' ),
	[ UNIT_HOURS ]: ( number ) =>
		_nx( 'hour', 'hours', number, 'Unit of a relative ticket sale date.', 'event-tickets' ),
	[ UNIT_DAYS ]: ( number ) => _nx( 'day', 'days', number, 'Unit of a relative ticket sale date.', 'event-tickets' ),
	[ UNIT_WEEKS ]: ( number ) =>
		_nx( 'week', 'weeks', number, 'Unit of a relative ticket sale date.', 'event-tickets' ),
};
