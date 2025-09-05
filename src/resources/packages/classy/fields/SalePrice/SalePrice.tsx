import { EndSelector, StartSelector } from '@tec/common/classy/components';
import { getSettings as getCommonSettings } from '@tec/common/classy/localizedData';
import { DateUpdateType } from '@tec/common/classy/types/FieldProps';
import { ToggleControl } from '@wordpress/components';
import { RefObject, useCallback, useRef } from '@wordpress/element';
import { _x } from '@wordpress/i18n';
import React, { Fragment, useState } from 'react';
import { CurrencyInput } from '../../components';
import { SalePriceDetails } from '../../types/Ticket';
import { TicketComponentProps } from '../../types/TicketComponentProps';

// Set up current date and common settings.
const currentDate = new Date();
const { dateWithYearFormat, startOfWeek } = getCommonSettings();

/**
 * Format a date as YYYY-MM-DD for sale date fields.
 *
 * @since TBD
 *
 * @param {Date} date The date to format.
 * @returns {string} Formatted date string.
 */
const formatSaleDate = ( date: Date ): string => {
	const month = ( date.getMonth() + 1 ).toString().padStart( 2, '0' );
	const day = date.getDate().toString().padStart( 2, '0' );
	const year = date.getFullYear();

	return `${ year }-${ month }-${ day }`;
};

type SalePriceProps = Omit< TicketComponentProps, 'label' | 'onChange' > & {
	value: SalePriceDetails;
	onChange: ( value: SalePriceDetails ) => void;
};

type SelectingDateType = DateUpdateType | false;

const salePriceLabel = _x( 'Add sale price', 'Label for the sale price field', 'event-tickets' );

/**
 * SalePrice component for managing the sale price and sale duration of a ticket.
 *
 * @since TBD
 *
 * @param {SalePriceProps} props
 * @return {JSX.Element} The rendered SalePrice component.
 */
export default function SalePrice( props: SalePriceProps ): JSX.Element {
	const { onChange, value } = props;

	const [ currentValue, setCurrentValue ] = useState< SalePriceDetails >( value );

	const { enabled: hasSalePrice, salePrice } = currentValue;
	const startDate = new Date( currentValue.startDate || currentDate.toISOString() );
	const endDate = new Date( currentValue.endDate || currentDate.toISOString() );

	const [ isSelectingDate, setIsSelectingDate ] = useState< SelectingDateType >( false );

	const ref: RefObject< HTMLDivElement > = useRef( null );

	const onDateInputClick = useCallback(
		( selecting: SelectingDateType ) => {
			if ( selecting === isSelectingDate ) {
				// Do nothing.
				return;
			}

			return setIsSelectingDate( selecting );
		},
		[ isSelectingDate ]
	);

	const setEndDate = useCallback(
		( date: Date ) => {
			const newValue: SalePriceDetails = {
				...currentValue,
				endDate: formatSaleDate( date ),
			};

			// If the new end date is before the current start date, update the start date to match.
			if ( date < new Date( currentValue.startDate ) ) {
				newValue.startDate = formatSaleDate( date );
			}

			setCurrentValue( newValue );
			onChange( newValue );
		},
		[ endDate, startDate, onChange ]
	);

	const setStartDate = useCallback(
		( date: Date ) => {
			const newValue: SalePriceDetails = {
				...currentValue,
				startDate: formatSaleDate( date ),
			};

			// If the new start date is after the current end date, update the end date to match.
			if ( date > new Date( currentValue.endDate ) ) {
				newValue.endDate = formatSaleDate( date );
			}

			setCurrentValue( newValue );
			onChange( newValue );
		},
		[ endDate, startDate, onChange ]
	);

	const onDateChange = ( selecting: DateUpdateType, date: string ) => {
		const newDate = new Date( date );
		if ( selecting === 'startDate' ) {
			setStartDate( newDate );
		} else {
			setEndDate( newDate );
		}
	};

	const onFieldChange = useCallback(
		( field: keyof SalePriceDetails, value: any ) => {
			const newValue: SalePriceDetails = {
				...currentValue,
				[ field ]: value,
			};

			setCurrentValue( newValue );
			onChange( newValue );
		},
		[ onChange, currentValue ]
	);

	return (
		<Fragment>
			<ToggleControl
				label={ salePriceLabel }
				__nextHasNoMarginBottom={ true }
				checked={ hasSalePrice }
				onChange={ ( value: boolean ) => onFieldChange( 'enabled', value ) }
			/>

			{ hasSalePrice && (
				<Fragment>
					<CurrencyInput
						label={ _x( 'Sale price', 'Label for the sale price field', 'event-tickets' ) }
						value={ salePrice }
						onChange={ ( value: string ) => onFieldChange( 'salePrice', value ) }
						required={ true }
					/>

					<div className="classy-field__input classy-field__input--sale-date" ref={ ref }>
						<StartSelector
							dateWithYearFormat={ dateWithYearFormat }
							endDate={ endDate }
							highlightTime={ false }
							isAllDay={ true }
							isMultiday={ true }
							isSelectingDate={ isSelectingDate }
							onChange={ onDateChange }
							onClick={ () => onDateInputClick( 'startDate' ) }
							onClose={ () => setIsSelectingDate( false ) }
							showTitle={ false }
							startDate={ startDate }
							startOfWeek={ startOfWeek }
							timeFormat={ '' }
							title={ _x( 'Start sales', 'Event date selection input title', 'event-tickets' ) }
						/>
						<EndSelector
							dateWithYearFormat={ dateWithYearFormat }
							endDate={ endDate }
							isAllDay={ true }
							isMultiday={ true }
							highlightTime={ false }
							isSelectingDate={ isSelectingDate }
							onChange={ onDateChange }
							onClick={ () => onDateInputClick( 'endDate' ) }
							onClose={ () => setIsSelectingDate( false ) }
							showAllDayLabel={ false }
							showTitle={ false }
							startDate={ startDate }
							startOfWeek={ startOfWeek }
							timeFormat={ '' }
							title={ _x( 'End sales', 'Event date selection input title', 'event-tickets' ) }
						/>
					</div>
				</Fragment>
			) }
		</Fragment>
	);
}
