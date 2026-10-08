/**
 * Loads current zone data onto the `moment.tz` WordPress's `wp-date` sets up.
 *
 * WordPress ships the zone data of its own release (2022g in 6.9), so a zone whose rules changed since, such as
 * Africa/Cairo's 2023 return to daylight saving time, resolved to other dates than the server stores, and the check of
 * the window could block a save the server accepts. Loading onto the same `moment.tz` keeps every zone WordPress added.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import moment from 'moment';

/**
 * Internal dependencies
 */
import zoneData from './zone-data.json';

moment.tz.load( zoneData );
