const {_x} = wp.i18n;
const {getLocalizedString} = tec.seating.utils;

const BAD_SERVICE_RESPONSE = 'BAD_SERVICE_RESPONSE';
const MISSING_REQUEST_PARAMETERS = 'MISSING_REQUEST_PARAMETERS';
const MISSING_EPHEMERAL_TOKEN = 'MISSING_EPHEMERAL_TOKEN';
const INVALID_SITE_PARAMETER = 'INVALID_SITE_PARAMETER';
const INVALID_EXPIRE_TIME_PARAMETER = 'INVALID_EXPIRE_TIME_PARAMETER';
const SITE_NOT_FOUND = 'SITE_NOT_FOUND';
const EPHEMERAL_TOKEN_STORE_ERROR = 'EPHEMERAL_TOKEN_STORE_ERROR';
const SITE_NOT_AUTHORIZED = 'SITE_NOT_AUTHORIZED';

const unknownError = _x('Unknown error', 'Error message', 'events-assigned-seating');

/**
 * A map from error codes to error messages.
 *
 * @since TBD
 *
 * @type {string: string}
 */
const errorCodeToMessageMap = {
	BAD_SERVICE_RESPONSE: getLocalizedString('bad-service-response', 'service-errors'),
	MISSING_REQUEST_PARAMETERS: getLocalizedString('missing-request-parameters', 'service-errors'),
	INVALID_SITE_PARAMETER: getLocalizedString('invalid-site-parameter', 'service-errors'),
	INVALID_EXPIRE_TIME_PARAMETER: getLocalizedString('invalid-expire-time-parameter', 'service-errors'),
	MISSING_EPHEMERAL_TOKEN: getLocalizedString('missing-ephemeral-token', 'service-errors'),
	SITE_NOT_FOUND: getLocalizedString('site-not-found', 'service-errors'),
	EPHEMERAL_TOKEN_STORE_ERROR: getLocalizedString('ephemeral-token-store-error', 'service-errors'),
	SITE_NOT_AUTHORIZED: getLocalizedString('site-not-authorized', 'service-errors'),
};

/**
 * Returns the error message for the given error code.
 *
 * @since TBD
 *
 * @param {string} errorCode The error code.
 *
 * @returns {string} The error message.
 */
function getErrorMessage(errorCode) {
	return errorCodeToMessageMap[errorCode] || unknownError;
}

window.tec = window.tec || {};
window.tec.seating = window.tec.seating || {};
window.tec.seating.errors = {
	...(window.tec.seating.errors || {}),
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