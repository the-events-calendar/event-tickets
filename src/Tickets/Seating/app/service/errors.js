const { _x } = wp.i18n;
import { getLocalizedString } from '@tec/tickets/seating/utils';

export const BAD_SERVICE_RESPONSE = 'BAD_SERVICE_RESPONSE';
export const MISSING_REQUEST_PARAMETERS = 'MISSING_REQUEST_PARAMETERS';
export const MISSING_EPHEMERAL_TOKEN = 'MISSING_EPHEMERAL_TOKEN';
export const INVALID_SITE_PARAMETER = 'INVALID_SITE_PARAMETER';
export const INVALID_EXPIRE_TIME_PARAMETER = 'INVALID_EXPIRE_TIME_PARAMETER';
export const SITE_NOT_FOUND = 'SITE_NOT_FOUND';
export const EPHEMERAL_TOKEN_STORE_ERROR = 'EPHEMERAL_TOKEN_STORE_ERROR';
export const SITE_NOT_AUTHORIZED = 'SITE_NOT_AUTHORIZED';

const unknownError = _x('Unknown error', 'Error message', 'event-tickets');

/**
 * A map from error codes to error messages.
 *
 * @since 5.16.0
 *
 * @type {string: string}
 */
const errorCodeToMessageMap = {
	BAD_SERVICE_RESPONSE: getLocalizedString(
		'bad-service-response',
		'service-errors'
	),
	MISSING_REQUEST_PARAMETERS: getLocalizedString(
		'missing-request-parameters',
		'service-errors'
	),
	INVALID_SITE_PARAMETER: getLocalizedString(
		'invalid-site-parameter',
		'service-errors'
	),
	INVALID_EXPIRE_TIME_PARAMETER: getLocalizedString(
		'invalid-expire-time-parameter',
		'service-errors'
	),
	MISSING_EPHEMERAL_TOKEN: getLocalizedString(
		'missing-ephemeral-token',
		'service-errors'
	),
	SITE_NOT_FOUND: getLocalizedString('site-not-found', 'service-errors'),
	EPHEMERAL_TOKEN_STORE_ERROR: getLocalizedString(
		'ephemeral-token-store-error',
		'service-errors'
	),
	SITE_NOT_AUTHORIZED: getLocalizedString(
		'site-not-authorized',
		'service-errors'
	),
};

/**
 * Returns the error message for the given error code.
 *
 * @since 5.16.0
 *
 * @param {string} errorCode The error code.
 *
 * @return {string} The error message.
 */
export function getErrorMessage(errorCode) {
	return errorCodeToMessageMap[errorCode] || unknownError;
}

window.tec = window.tec || {};
window.tec.tickets.seating = window.tec.tickets.seating || {};
window.tec.tickets.seating.service = window.tec.tickets.seating.service || {};
window.tec.tickets.seating.service.errors = {
	...(window.tec.tickets.seating.service.errors || {}),
	BAD_SERVICE_RESPONSE,
	MISSING_REQUEST_PARAMETERS,
	MISSING_EPHEMERAL_TOKEN,
	INVALID_SITE_PARAMETER,
	INVALID_EXPIRE_TIME_PARAMETER,
	SITE_NOT_FOUND,
	EPHEMERAL_TOKEN_STORE_ERROR,
	SITE_NOT_AUTHORIZED,
	getErrorMessage,
};
