import React, { Fragment, useState } from 'react';
import { ToggleControl } from '@wordpress/components';
import { RefObject, useCallback, useRef } from '@wordpress/element';
import { _x } from '@wordpress/i18n';
import { StartSelector, EndSelector } from '@tec/common/classy/components';
import { StartOfWeek } from '@tec/common/classy/types/StartOfWeek';
import { TicketComponentProps } from '../../types/TicketComponentProps';
import { SalePriceDetails } from '../../types/Ticket';
import { CurrencyInput } from '../../components';

// todo: These should be based on site settings.
const currentDate = new Date();
const dateWithYearFormat = 'Y-m-d';
const startOfWeek: StartOfWeek = 0;

type SalePriceProps = Omit< TicketComponentProps, 'label' | 'onChange' > & {
	value: SalePriceDetails;
	onChange: ( value: SalePriceDetails ) => void;
};

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

	const { enabled: hasSalePrice, salePrice, startDate, endDate } = currentValue;

	const [ isSelectingDate, setIsSelectingDate ] = useState< 'start' | 'end' | false >( false );

	const ref: RefObject< HTMLDivElement > = useRef( null );

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

	const onDateChange = ( selecting: 'start' | 'end', date: string ) => {
		const newDate = new Date( date );
		if ( selecting === 'start' ) {
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
							highightTime={ false }
							isAllDay={ true }
							isMultiday={ true }
							isSelectingDate={ isSelectingDate }
							onChange={ onDateChange }
							onClick={ () => onDateInputClick( 'start' ) }
							onClose={ () => setIsSelectingDate( false ) }
							startDate={ startDate }
							startOfWeek={ startOfWeek }
							timeFormat={ '' }
							title={ _x( 'On sale from', 'Event date selection input title', 'the-events-calendar' ) }
						/>
						<span className="classy-field__input--date-separator">
							{ _x( 'to', 'Separator between start and end date inputs', 'the-events-calendar' ) }
						</span>
						<EndSelector
							currentDate={ currentDate }
							dateWithYearFormat={ dateWithYearFormat }
							endDate={ endDate }
							isMultiday={ true }
							isSelectingDate={ isSelectingDate }
							onChange={ onDateChange }
							onClick={ () => onDateInputClick( 'end' ) }
							onClose={ () => setIsSelectingDate( false ) }
							showPopover={ isSelectingDate === 'end' }
							startDate={ startDate }
							startOfWeek={ startOfWeek }
						/>
					</div>
				</Fragment>
			) }
		</Fragment>
	);
}
