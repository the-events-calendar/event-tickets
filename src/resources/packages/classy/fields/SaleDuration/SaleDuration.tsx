import { DateUpdateType } from '@tec/common/classy/types/FieldProps';
import * as React from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useMemo, useRef, useState } from '@wordpress/element';
import { _x } from '@wordpress/i18n';
import { getSettings as getCommonSettings } from '@tec/common/classy/localizedData';
import { EndSelector, StartSelector } from '@tec/common/classy/components';
import { getDate } from '@wordpress/date';


const { startOfWeek, dateWithYearFormat, timeFormat } = getCommonSettings();
const saleStart = '2025-09-23 08:00:00';
const saleEnd = '2025-10-23 17:00:00';
const isMultiday = true;

type NewDatesReturn = {
	newStartDate: Date;
	newEndDate: Date;
	notify: {
		start: boolean;
		end: boolean;
	};
};

type SelectingDateType = DateUpdateType | false;

/**
 * Calculates new start and end dates based on user updates.
 *
 * @since TBD
 *
 * @param {Date} endDate The current end date.
 * @param {Date} startDate The current start date.
 * @param {'start' | 'end'} updated Indicates whether the start or end date was updated.
 * @param {string} newDate The new date string provided by the user.
 * @return {NewDatesReturn} An object defining the new start and end dates, and whether the user needs to be notified
 *     of the implicit change of either.
 */
function getNewStartEndDates(
	endDate: Date,
	startDate: Date,
	updated: SelectingDateType,
	newDate: string
): NewDatesReturn {
	let newStartDate: Date;
	let newEndDate: Date;
	let notify = { start: false, end: false };

	if ( updated === 'startDate' ) {
		// The user has updated the start date.
		newStartDate = getDate( newDate );
		newEndDate = endDate;

		// The start date is after the current end date: set the end date to the start date.
		if ( newStartDate.getTime() >= endDate.getTime() ) {
			newEndDate = new Date( newStartDate.getTime() );
			notify.end = true;
		}
	} else {
		// The user has updated the end date.
		newStartDate = startDate;
		newEndDate = getDate( newDate );

		// The end date is before the current start date: set the start date to the end date.
		if ( newEndDate.getTime() <= startDate.getTime() ) {
			newStartDate = new Date( newEndDate.getTime() );
			notify.start = true;
		}
	}

	return { newStartDate, newEndDate, notify };
}

export default function SaleDuration() {
	// todo: Obtain relevant values from the store. See EventDateTime for reference.

	const { editPost } = useDispatch( 'core/editor' );

	const [ isSelectingDate, setIsSelectingDate ] = useState< SelectingDateType >( false );
	const [ dates, setDates ] = useState( {
		start: getDate( saleStart ),
		end: getDate( saleEnd ),
	} );
	const [ isMultidayValue, setIsMultidayValue ] = useState( true );
	const { start: startDate, end: endDate } = dates;
	const [ higlightStartTime, setHighlightStartTime ] = useState( false );
	const [ highlightEndTime, setHighlightEndTime ] = useState( false );

	// Used in dependencies.
	const startDateIsoString = startDate.toISOString();
	const endDateIsoString = endDate.toISOString();

	const onDateChange = useCallback(
		( updated: DateUpdateType, newDate: string ): void => {
			const { newStartDate, newEndDate, notify } = getNewStartEndDates( endDate, startDate, updated, newDate );

			// editPost( {
			// 	meta: {
			// 		[ METADATA_EVENT_START_DATE ]: format( phpDateMysqlFormat, newStartDate ),
			// 		[ METADATA_EVENT_END_DATE ]: format( phpDateMysqlFormat, newEndDate ),
			// 	},
			// } );

			// If the start date and end date are on the same year, month, day, then it's not multiday.
			if (
				newStartDate.getFullYear() === newEndDate.getFullYear() &&
				newStartDate.getMonth() === newEndDate.getMonth() &&
				newStartDate.getDate() === newEndDate.getDate()
			) {
				setIsMultidayValue( false );
			}

			setDates( { start: newStartDate, end: newEndDate } );
			setIsSelectingDate( false );
			setHighlightStartTime( notify.start );
			setHighlightEndTime( notify.end );
		},
		[ endDateIsoString, startDateIsoString, editPost ]
	);

	const onDateInputClick = useCallback(
		( selecting: SelectingDateType ) => {
			// If clicking the same input when already open, do nothing.
			if ( selecting === isSelectingDate ) {
				return;
			}

			return setIsSelectingDate( selecting );
		},
		[ isSelectingDate ]
	);

	const endSelector = useMemo( () => {
		return (
			<EndSelector
				dateWithYearFormat={ dateWithYearFormat }
				endDate={ endDate }
				highlightTime={ highlightEndTime }
				isMultiday={ isMultidayValue }
				isSelectingDate={ isSelectingDate }
				onChange={ onDateChange }
				onClick={ () => onDateInputClick( 'end' ) }
				onClose={ () => setIsSelectingDate( false ) }
				startDate={ startDate }
				startOfWeek={ startOfWeek }
				timeFormat={ timeFormat }
			/>
		);
	}, [
		dateWithYearFormat,
		endDateIsoString,
		isMultidayValue,
		isSelectingDate,
		startDateIsoString,
		startOfWeek,
		timeFormat,
	] );

	return (
		<div className="classy-field__input classy-field__input--sale-duration">
			<div className="classy-field__input-title">
				<h4>{ _x( 'Start sales', 'Sale start date input title', 'event-tickets' ) }</h4>
			</div>

			<StartSelector
				dateWithYearFormat={ dateWithYearFormat }
				endDate={ endDate }
				highlightTime={ true }
				isAllDay={ false }
				isMultiday={ true }
				isSelectingDate={ isSelectingDate }
				onChange={ onDateChange }
				onClick={ () => onDateInputClick( 'startDate' ) }
				onClose={ () => setIsSelectingDate( false ) }
				startDate={ startDate }
				startOfWeek={ startOfWeek }
				timeFormat={ timeFormat }
			/>

			<div className="classy-field__input-title">
				<h4>{ _x( 'End sales', 'Sale end date input title', 'event-tickets' ) }</h4>
			</div>
			{ endSelector }
		</div>
	);
}
