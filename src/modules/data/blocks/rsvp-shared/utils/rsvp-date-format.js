/**
 * Internal dependencies
 */
import { globals } from '@moderntribe/common/utils';

/**
 * PHP date format used across the RSVP editor (date inputs, summaries).
 *
 * Follows the WordPress date format like the legacy V1 RSVP UI did, instead of
 * TEC's compact datepicker format.
 *
 * @return {string} A PHP date format string, e.g. `F j, Y`.
 */
export const getRsvpDateFormat = () => globals.dateSettings().formats?.date || 'F j, Y';
