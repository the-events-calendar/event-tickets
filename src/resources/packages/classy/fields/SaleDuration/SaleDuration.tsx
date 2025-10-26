import * as React from 'react';
import { ClassyFieldGroup, TimePicker } from '@tec/common/classy/components';
import { areDatesOnSameDay, areDatesOnSameTime, isValidDate } from '@tec/common/classy/functions';
import { getSettings as getCommonSettings } from '@tec/common/classy/localizedData';
import { DateTimeUpdateType, DateUpdateType } from '@tec/common/classy/types/FieldProps';
import { format } from '@wordpress/date';
import { RefObject, useCallback, useMemo, useState } from '@wordpress/element';
import { _x } from '@wordpress/i18n';
import DatePicker from './DatePicker';

const { startOfWeek, dateWithYearFormat, timeFormat, timeInterval } = getCommonSettings();

type NewDatesReturn = {
	newStartDate: Date;
	newEndDate: Date;
	notify: {
		endDate: boolean;
		endTime: boolean;
		startDate: boolean;
		startTime: boolean;
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
	updated: DateTimeUpdateType,
	newDate: string
): NewDatesReturn => {
	const duration = endDate.getTime() - startDate.getTime();
	let newStartDate = startDate;
	let newEndDate = endDate;
	let notify = { startDate: false, startTime: false, endDate: false, endTime: false };

	try {
		switch ( updated ) {
			case 'startDate':
				newStartDate = new Date( newDate );
				if ( newStartDate.getTime() > endDate.getTime() ) {
					newEndDate = new Date( newStartDate.getTime() + duration );
				}

				break;
			case 'startTime':
				newStartDate = new Date( newDate );

				if ( newStartDate.getTime() >= endDate.getTime() ) {
					newEndDate = new Date( newStartDate.getTime() + duration );
				}

				break;
			case 'endDate':
				newEndDate = new Date( newDate );
				if ( newEndDate.getTime() <= startDate.getTime() ) {
					newStartDate = new Date( newEndDate.getTime() - duration );
				}

				break;
			case 'endTime':
				newEndDate = new Date( newDate );

				if ( newEndDate.getTime() < startDate.getTime() ) {
					newStartDate = new Date( newEndDate.getTime() - duration );
				}
				break;
		}

		// Highlight the appropriate fields if they actually changed as a consequence of the update.
		notify.startDate = updated !== 'startDate' && ! areDatesOnSameDay( startDate, newStartDate );
		notify.startTime = updated !== 'startTime' && ! areDatesOnSameTime( startDate, newStartDate );
		notify.endDate = updated !== 'endDate' && ! areDatesOnSameDay( endDate, newEndDate );
		notify.endTime = updated !== 'endTime' && ! areDatesOnSameTime( endDate, newEndDate );
	} catch ( e ) {
		// Something went wrong while processing the dates, return the values unchanged and notify no field.
		newStartDate = startDate;
		newEndDate = endDate;
		notify = { startDate: false, startTime: false, endDate: false, endTime: false };
	}

	return { newStartDate, newEndDate, notify };
};

type SaleDurationProps = {
	saleStart: Date | '';
	saleEnd: Date | '';
	onChange: ( saleStart: Date, saleEnd: Date ) => void;
};

// Set default start/end dates for when nothing is selected.
const defaultStartDate = new Date();
const defaultEndDate = new Date();
defaultEndDate.setHours( 23, 59, 59, 999 );

/**
 * SaleDuration component.
 *
 * Displays date and time pickers for selecting sale start and end dates.
 *
 * @since TBD
 *
 * @param {SaleDurationProps} props The properties for the SaleDuration component.
 * @return {React.JSX.Element} The rendered SaleDuration component.
 */
export default function SaleDuration( props: SaleDurationProps ): React.JSX.Element {
	const { onChange } = props;

	const [ isSelectingDate, setIsSelectingDate ] = useState< SelectingDateType >( false );
	const [ saleStart, setSaleStart ] = useState< Date >( props.saleStart || defaultStartDate );
	const [ saleEnd, setSaleEnd ] = useState< Date >( props.saleEnd || defaultEndDate );
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
			setHighlightStartTime( notify.startTime );
			setHighlightEndTime( notify.endTime );
			onChange( newStartDate, newEndDate );
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
						startDate={ saleStart }
						highlight={ higlightStartTime }
						onChange={ ( date: Date ): void => {
							onDateChange( 'startTime', format( 'Y-m-d H:i:s', date ) );
						} }
						timeFormat={ timeFormat }
						timeInterval={ timeInterval }
						type="startTime"
					/>
				</div>
			</ClassyFieldGroup>
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
						startDate={ saleStart }
						endDate={ saleEnd }
						highlight={ highlightEndTime }
						onChange={ ( date: Date ): void => {
							onDateChange( 'endTime', format( 'Y-m-d H:i:s', date ) );
						} }
						timeFormat={ timeFormat }
						timeInterval={ timeInterval }
						type="endTime"
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
