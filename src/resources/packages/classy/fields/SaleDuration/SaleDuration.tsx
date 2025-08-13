import * as React from 'react';
import { Fragment } from 'react';
import { ToggleControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { RefObject, useCallback, useMemo, useRef, useState } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';
import { TicketComponentProps } from '../../types/TicketComponentProps';
import { StartOfWeek } from '@tec/common/classy/types/StartOfWeek';
import { default as StartSelector } from './StartSelector';
import { default as EndSelector } from './EndSelector';
import { getDate } from '@wordpress/date';

// todo: These should be based on site settings.
const currentDate = new Date();
const dateWithYearFormat = 'Y-m-d';
const startOfWeek: StartOfWeek = 0;
const phpDateMysqlFormat = 'Y-m-d H:i:s';
const timeFormat = 'g:i a';
const saleStart = '2025-06-23 08:00:00';
const saleEnd = '2025-07-23 17:00:00';
const isMultiday = true;

type DateTimeRefs = {
	endTimeHours: number;
	endTimeMinutes: number;
	multiDayDuration: number;
	singleDayDuration: number;
	startTimeHours: number;
	startTimeMinutes: number;
};

type NewDatesReturn = {
	newStartDate: Date;
	newEndDate: Date;
	notify: {
		start: boolean;
		end: boolean;
	};
};

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
	updated: 'start' | 'end',
	newDate: string
): NewDatesReturn {
	let newStartDate: Date;
	let newEndDate: Date;
	let notify = { start: false, end: false };

	if ( updated === 'start' ) {
		// The user has updated the start date.
		newStartDate = getDate( newDate );
		newEndDate = endDate;

		if ( newStartDate.getTime() >= endDate.getTime() ) {
			// The start date is after the current end date: set the end date to the start date.
			newEndDate = new Date( newStartDate.getTime() );
			notify.end = true;
		}
	} else {
		// The user has updated the end date.
		newStartDate = startDate;
		newEndDate = getDate( newDate );

		if ( newEndDate.getTime() <= startDate.getTime() ) {
			// The end date is before the current start date: set the start date to the end date.
			newStartDate = new Date( newEndDate.getTime() );
			notify.start = true;
		}
	}

	return { newStartDate, newEndDate, notify };
}

export default function SaleDuration() {
	// todo: Obtain relevant values from the store. See EventDateTime for reference.

	const { editPost } = useDispatch( 'core/editor' );

	const [ isSelectingDate, setIsSelectingDate ] = useState< 'start' | 'end' | false >( false );
	const [ dates, setDates ] = useState( {
		start: getDate( saleStart ),
		end: getDate( saleEnd ),
	} );
	const [ isMultidayValue, setIsMultidayValue ] = useState( true );
	const { start: startDate, end: endDate } = dates;
	const [ higlightStartTime, setHighlightStartTime ] = useState( false );
	const [ highlightEndTime, setHighlightEndTime ] = useState( false );

	// Store a reference to some ground values to allow the toggle of multi-day and all-day correctly.
	const refs = useRef( {
		startTimeHours: startDate.getHours(),
		startTimeMinutes: startDate.getMinutes(),
		endTimeHours: endDate.getHours(),
		endTimeMinutes: endDate.getMinutes(),
		// The default single-day duration is 9 hours.
		singleDayDuration: isMultiday ? 9 * 60 * 60 * 1000 : dates.end.getTime() - dates.start.getTime(),
		// The default multi-day duration is 24 hours.
		multiDayDuration: isMultiday ? dates.end.getTime() - dates.start.getTime() : 24 * 60 * 60 * 1000,
	} );

	// Used in dependencies.
	const startDateIsoString = startDate.toISOString();
	const endDateIsoString = endDate.toISOString();

	const onDateChange = useCallback(
		( updated: 'start' | 'end', newDate: string ): void => {
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
		( selecting: 'start' | 'end' ) => {
			if ( selecting === isSelectingDate ) {
				// Do nothing.
				return;
			}

			return setIsSelectingDate( selecting );
		},
		[ isSelectingDate ]
	);

	const startSelector = useMemo( () => {
		return (
			<StartSelector
				dateWithYearFormat={ dateWithYearFormat }
				endDate={ endDate }
				highightTime={ higlightStartTime }
				isMultiday={ isMultidayValue }
				isSelectingDate={ isSelectingDate }
				onChange={ onDateChange }
				onClick={ () => onDateInputClick( 'start' ) }
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
			{ startSelector }

			<div className="classy-field__input-title">
				<h4>{ _x( 'End sales', 'Sale end date input title', 'event-tickets' ) }</h4>
			</div>
			{ endSelector }
		</div>
	);
}
