/**
 * Internal dependencies
 */
import { globals, moment as momentUtil } from '@moderntribe/common/utils';
import * as utils from '../../../utils';

/**
 * Resolves RSVP capacity from a V2 ticket REST payload.
 *
 * @param {Object} rsvp Ticket REST response object.
 * @return {number|string} Capacity value or empty string for unlimited.
 */
const resolveV2Capacity = ( rsvp ) => {
	if ( rsvp.stock_mode === 'unlimited' ) {
		return '';
	}

	if ( rsvp.capacity != null && Number( rsvp.capacity ) >= 0 ) {
		return Number( rsvp.capacity );
	}

	return '';
};

/**
 * Builds RSVP details and temp details payloads from moment objects.
 *
 * @param {Object} params                    Normalization parameters.
 * @param {string} params.title              RSVP title.
 * @param {string} params.description        RSVP description.
 * @param {*}      params.capacity           RSVP capacity.
 * @param {*}      params.notGoingResponses   Whether not-going responses are enabled.
 * @param {Object} params.startMoment        Start moment object.
 * @param {Object} params.endMoment          End moment object.
 * @return {Object} Normalized RSVP state payloads.
 */
export const normalizeRSVPResponse = ( {
	title,
	description,
	capacity,
	notGoingResponses,
	startMoment,
	endMoment,
} ) => {
	const datePickerFormat = globals.tecDateSettings().datepickerFormat;
	const startDateInput = datePickerFormat
		? startMoment.format( momentUtil.toFormat( datePickerFormat ) )
		: momentUtil.toDate( startMoment );
	const endDateInput = datePickerFormat
		? endMoment.format( momentUtil.toFormat( datePickerFormat ) )
		: momentUtil.toDate( endMoment );

	const normalizedTitle = utils.normalizeTitle( title );
	const normalizedDescription = utils.normalizeDescription( description );

	return {
		details: {
			title: normalizedTitle,
			description: normalizedDescription,
			capacity,
			notGoingResponses,
			startDate: momentUtil.toDate( startMoment ),
			startDateInput,
			startDateMoment: startMoment.clone().startOf( 'day' ),
			endDate: momentUtil.toDate( endMoment ),
			endDateInput,
			endDateMoment: endMoment.clone().seconds( 0 ),
			startTime: momentUtil.toDatabaseTime( startMoment ),
			endTime: momentUtil.toDatabaseTime( endMoment ),
			startTimeInput: momentUtil.toTime( startMoment ),
			endTimeInput: momentUtil.toTime( endMoment ),
		},
		tempDetails: {
			tempTitle: normalizedTitle,
			tempDescription: normalizedDescription,
			tempCapacity: capacity,
			tempNotGoingResponses: notGoingResponses,
			tempStartDate: momentUtil.toDate( startMoment ),
			tempStartDateInput: startDateInput,
			tempStartDateMoment: startMoment.clone().startOf( 'day' ),
			tempEndDate: momentUtil.toDate( endMoment ),
			tempEndDateInput: endDateInput,
			tempEndDateMoment: endMoment.clone().seconds( 0 ),
			tempStartTime: momentUtil.toDatabaseTime( startMoment ),
			tempEndTime: momentUtil.toDatabaseTime( endMoment ),
			tempStartTimeInput: momentUtil.toTime( startMoment ),
			tempEndTimeInput: momentUtil.toTime( endMoment ),
		},
	};
};

/**
 * Normalizes a V1 WordPress RSVP post response.
 *
 * @param {Object} rsvp WordPress RSVP post object.
 * @return {Object|null} Normalized RSVP state or null when invalid.
 */
export const normalizeRSVPResponseFromV1Post = ( rsvp ) => {
	const { meta = {} } = rsvp;
	const startMoment = momentUtil.toMoment( meta[ utils.KEY_TICKET_START_DATE ] );
	const endMoment = momentUtil.toMoment( meta[ utils.KEY_TICKET_END_DATE ] );
	const capacity = meta[ utils.KEY_TICKET_CAPACITY ] >= 0 ? meta[ utils.KEY_TICKET_CAPACITY ] : '';

	return {
		id: rsvp.id,
		goingCount: parseInt( meta[ utils.KEY_TICKET_GOING_COUNT ], 10 ) || 0,
		notGoingCount: parseInt( meta[ utils.KEY_TICKET_NOT_GOING_COUNT ], 10 ) || 0,
		hasAttendeeInfoFields: meta[ utils.KEY_TICKET_HAS_ATTENDEE_INFO_FIELDS ],
		fieldLabels: rsvp.field_labels || [],
		...normalizeRSVPResponse( {
			title: rsvp.title,
			description: rsvp.excerpt,
			capacity,
			notGoingResponses: meta[ utils.KEY_TICKET_SHOW_NOT_GOING ],
			startMoment,
			endMoment,
		} ),
	};
};

/**
 * Normalizes a V2 TEC REST ticket response.
 *
 * @param {Object} rsvp V2 ticket object.
 * @param {Object} options              Normalization options.
 * @param {string} options.title        Override title.
 * @param {string} options.description  Override description.
 * @return {Object} Normalized RSVP state.
 */
export const normalizeRSVPResponseFromV2Ticket = ( rsvp, options = {} ) => {
	const startMoment = momentUtil.toMoment( rsvp.start_date );
	const endMoment = momentUtil.toMoment( rsvp.end_date );
	const capacity = resolveV2Capacity( rsvp );
	const title = options.title ?? utils.normalizeTitle( rsvp.title );
	const description =
		options.description ?? utils.normalizeDescription( rsvp.description, rsvp.excerpt );

	return {
		id: rsvp.id,
		goingCount: parseInt( rsvp.going_count || rsvp.sold || 0, 10 ),
		notGoingCount: parseInt( rsvp.not_going_count || 0, 10 ),
		hasAttendeeInfoFields: rsvp.has_attendee_info_fields || false,
		fieldLabels: rsvp.field_labels || [],
		...normalizeRSVPResponse( {
			title,
			description,
			capacity,
			notGoingResponses: rsvp.show_not_going || false,
			startMoment,
			endMoment,
		} ),
	};
};
