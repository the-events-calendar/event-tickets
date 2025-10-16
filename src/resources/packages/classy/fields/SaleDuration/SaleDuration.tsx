import * as React from 'react';
import { ClassyFieldGroup, StartSelector, TimePicker } from '@tec/common/classy/components';
import { isValidDate } from '@tec/common/classy/functions';
import { getSettings as getCommonSettings } from '@tec/common/classy/localizedData';
import { DateTimeUpdateType, DateUpdateType } from '@tec/common/classy/types/FieldProps';
import { getDate, format } from '@wordpress/date';
import { RefObject, useCallback, useMemo, useState } from '@wordpress/element';
import { _x } from '@wordpress/i18n';
import DatePicker from './DatePicker';

const { startOfWeek, dateWithYearFormat, timeFormat, timeInterval } = getCommonSettings();

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
const getNewStartEndDates = (
	endDate: Date,
	startDate: Date,
	updated: SelectingDateType,
	newDate: string
): NewDatesReturn => {
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
};

type SaleDurationProps = {
	saleStart: Date | '';
	saleEnd: Date | '';
};

// Set default start/end dates for when nothing is selected.
const defaultStartDate = new Date();
const defaultEndDate = new Date();
defaultEndDate.setHours( 23, 59, 59, 999 );

type Dates = {
	start: Date;
	end: Date;
};

export default function SaleDuration( props: SaleDurationProps ): React.JSX.Element {
	// const { saleStart, saleEnd } = props;

	const [ isSelectingDate, setIsSelectingDate ] = useState< SelectingDateType >( false );
	const [ saleStart, setSaleStart ] = useState< Date >( props.saleStart || defaultStartDate );
	const [ saleEnd, setSaleEnd ] = useState< Date >( props.saleEnd || defaultEndDate );

	// const [ dates, setDates ] = useState< Dates >( {
	// 	start: saleStart || defaultStartDate,
	// 	end: saleEnd || defaultEndDate,
	// } );

	const [ higlightStartTime, setHighlightStartTime ] = useState( false );
	const [ highlightEndTime, setHighlightEndTime ] = useState( false );

	const onDateChange = useCallback(
		( updated: DateTimeUpdateType, newDate: string ): void => {
			// Ensure we have a valid new date.
			if ( ! isValidDate( newDate ) ) {
				return;
			}

			const { newStartDate, newEndDate, notify } = getNewStartEndDates( saleEnd, saleStart, updated, newDate );

			setSaleStart( newStartDate );
			setSaleEnd( newEndDate );
			setIsSelectingDate( false );
			setHighlightStartTime( notify.start );
			setHighlightEndTime( notify.end );
		},
		[ saleStart, saleEnd ]
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

	const endRef: RefObject< HTMLDivElement > = React.useRef( null );
	const startRef: RefObject< HTMLDivElement > = React.useRef( null );

	const startSelector = useMemo( () => {
		return (
			<ClassyFieldGroup>
				<div ref={ startRef } />
				<DatePicker
					anchor={ startRef.current }
					dateWithYearFormat={ dateWithYearFormat }
					endDate={ saleEnd }
					isSelectingDate={ isSelectingDate }
					onChange={ onDateChange }
					onClick={ () => onDateInputClick( 'startDate' ) }
					onClose={ () => setIsSelectingDate( false ) }
					showPopover={ isSelectingDate === 'startDate' }
					startDate={ saleStart }
					startOfWeek={ startOfWeek }
					currentDate={ saleStart }
				/>
				<div className="classy-field__input classy-field__input--start-time">
					<TimePicker
						currentDate={ saleStart }
						endDate={ saleEnd }
						highlight={ higlightStartTime }
						onChange={ ( date: Date ): void => {
							onDateChange( 'startDate', format( 'Y-m-d H:i:s', date ) );
						} }
						timeFormat={ timeFormat }
						timeInterval={ timeInterval }
					/>
				</div>
			</ClassyFieldGroup>

			// <StartSelector
			// 	dateWithYearFormat={ dateWithYearFormat }
			// 	endDate={ saleStart }
			// 	highlightTime={ higlightStartTime }
			// 	isAllDay={ false }
			// 	isMultiday={ false }
			// 	isSelectingDate={ isSelectingDate }
			// 	onChange={ onDateChange }
			// 	onClick={ () => onDateInputClick( 'startDate' ) }
			// 	onClose={ () => setIsSelectingDate( false ) }
			// 	showTitle={ false }
			// 	startDate={ saleStart }
			// 	startOfWeek={ startOfWeek }
			// 	timeFormat={ timeFormat }
			// />
		);
	}, [ dateWithYearFormat, saleStart, isSelectingDate, startOfWeek, timeFormat ] );

	const endSelector = useMemo( () => {
		return (
			<ClassyFieldGroup>
				<div ref={ endRef } />
				<DatePicker
					anchor={ endRef.current }
					dateWithYearFormat={ dateWithYearFormat }
					endDate={ saleEnd }
					isSelectingDate={ isSelectingDate }
					onChange={ onDateChange }
					onClick={ () => onDateInputClick( 'endDate' ) }
					onClose={ () => setIsSelectingDate( false ) }
					showPopover={ isSelectingDate === 'endDate' }
					startDate={ saleStart }
					startOfWeek={ startOfWeek }
					currentDate={ saleEnd }
				/>
				<div className="classy-field__input classy-field__input--end-time">
					<TimePicker
						currentDate={ saleEnd }
						endDate={ null }
						highlight={ highlightEndTime }
						onChange={ ( date: Date ): void => {
							onDateChange( 'endDate', format( 'Y-m-d H:i:s', date ) );
						} }
						timeFormat={ timeFormat }
						timeInterval={ timeInterval }
					/>
				</div>
			</ClassyFieldGroup>
		);
	}, [ endRef, dateWithYearFormat, saleStart, saleEnd, isSelectingDate, startOfWeek, timeFormat ] );

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
