import React, { Fragment, useState } from 'react';
import { ToggleControl } from '@wordpress/components';
import { useCallback } from '@wordpress/element';
import { _x } from '@wordpress/i18n';
import { EndSelector, StartSelector } from '@tec/common/classy/components';
import { getSettings as getCommonSettings } from '@tec/common/classy/localizedData';
import { DateUpdateType } from '@tec/common/classy/types/FieldProps';
import { phpDateMysqlFormat as saleDateFormat } from '@tec/common/classy/constants';
import { CurrencyInput } from '../../components';
import { formatSaleDate } from '../../functions/tickets';
import { SalePriceDetails } from '../../types/Ticket';
import { TicketComponentProps } from '../../types/TicketComponentProps';

// Set up current date and common settings.
const currentDate = new Date();
const { startOfWeek } = getCommonSettings();

// Set the time to noon to avoid timezone issues when comparing dates.
currentDate.setHours( 12 );

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
	const startDate = new Date( currentValue.startDate || formatSaleDate( currentDate ) );
	const endDate = new Date( currentValue.endDate || formatSaleDate( currentDate ) );

	const [ isSelectingDate, setIsSelectingDate ] = useState< SelectingDateType >( false );

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

	const onDateChange = useCallback(
		( selecting: DateUpdateType, date: string ) => {
			const newDate = new Date( date );
			const formattedDate = formatSaleDate( newDate );
			const newValue: SalePriceDetails = { ...currentValue };

			// If the new date is the same as the current date, do nothing.
			if ( formattedDate === currentValue[ selecting ] ) {
				return;
			}

			if ( selecting === 'startDate' ) {
				newValue.startDate = newDate;

				// If the new start date is after the current end date, update the end date to match.
				if ( newDate > new Date( currentValue.endDate ) ) {
					newValue.endDate = newDate;
				}
			} else if ( selecting === 'endDate' ) {
				newValue.endDate = newDate;

				// If the new end date is before the current start date, update the start date to match.
				if ( newDate < new Date( currentValue.startDate ) ) {
					newValue.startDate = newDate;
				}
			}

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

					<div className="classy-field__input classy-field__input--sale-date">
						<StartSelector
							dateWithYearFormat={ saleDateFormat }
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
							dateWithYearFormat={ saleDateFormat }
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
