import React, { Fragment, useState } from 'react';
import { ToggleControl } from '@wordpress/components';
import { RefObject, useCallback, useRef } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';
import { DatePicker } from '@tec/common/classy/components';
import { TicketComponentProps } from '../../types/TicketComponentProps';
import { CurrencyInput} from '../../components';
import { StartOfWeek } from '@tec/common/classy/types/StartOfWeek';

// todo: These should be based on site settings.
const currentDate = new Date();
const dateWithYearFormat = 'Y-m-d';
const startOfWeek: StartOfWeek = 0;


export default function SalePrice( props: TicketComponentProps ) : JSX.Element {

	const [ hasSalePrice, setHasSalePrice ] = useState<boolean>( false );
	const [ salePrice, setSalePrice ] = useState<string>( '' );

	const salePriceLabel = _x( 'Add sale price', 'Label for the sale price field', 'event-tickets' );

	const ref: RefObject< HTMLDivElement > = useRef( null );

	const [ startDate, setStartDate ] = useState<Date | null>( null );
	const [ endDate, setEndDate ] = useState<Date | null>( null );
	const [ isSelectingDate, setIsSelectingDate ] = useState< 'start' | 'end' | false >( false );

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

	return (
		<Fragment>
			<ToggleControl
				label={ salePriceLabel }
				__nextHasNoMarginBottom={ true }
				checked={ hasSalePrice }
				onChange={ ( value: boolean ) => setHasSalePrice( value ) }
			/>

			{ hasSalePrice && (
				<Fragment>
					<CurrencyInput
						label={ _x( 'Sale price', 'Label for the sale price field', 'event-tickets' ) }
						value={ salePrice }
						onChange={ ( value: string ) => setSalePrice( value ) }
						required={ true }
					/>

					<div className="classy-field__input classy-field__input--sale-date" ref={ ref }>
						<div className="classy-field__input-title">
							<h4>{ _x( 'On sale from', 'Event date selection input title', 'the-events-calendar' ) }</h4>
						</div>
						<DatePicker
							anchor={ ref.current }
							dateWithYearFormat={ dateWithYearFormat }
							endDate={ endDate }
							showPopover={ isSelectingDate === 'start' }
							onClick={ () => onDateInputClick( 'start' ) }
							onClose={ () => setIsSelectingDate( false ) }
							onChange={ onDateChange }
							startDate={ startDate }
							startOfWeek={ startOfWeek }
							currentDate={ currentDate }
							isMultiday={ true }
							isSelectingDate={ isSelectingDate }
						/>
						<span className="classy-field__input--date-separator">
							{ _x( 'to', 'Separator between start and end date inputs', 'the-events-calendar' ) }
						</span>
						<DatePicker
							anchor={ref.current}
							currentDate={ currentDate }
							dateWithYearFormat={ dateWithYearFormat }
							endDate={ endDate }
							isMultiday={ true }
							isSelectingDate={isSelectingDate }
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
