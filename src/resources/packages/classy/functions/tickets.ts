import { format } from '@wordpress/date';
import {
	phpDateMysqlFormat as saleDateFormat,
	phpDateTimeMysqlFormat as saleDurationDateFormat,
} from '@tec/common/classy/constants';

/**
 * Format a date as YYYY-MM-DD for sale date fields.
 *
 * @since TBD
 *
 * @param {Date} date The date to format.
 * @returns {string} Formatted date string.
 */
export const formatSaleDate = ( date: Date ): string => {
	return format( saleDateFormat, date );
};

/**
 * Format a date as YYYY-MM-DD HH:MM:SS for sale duration fields.
 *
 * @since TBD
 *
 * @param {Date} date The date to format.
 * @returns {string} Formatted date string.
 */
export const formatSaleDurationDate = ( date: Date ): string => {
	return format( saleDurationDateFormat, date );
};
