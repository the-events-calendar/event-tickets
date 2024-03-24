/**
 * External dependencies
 */
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { NumericFormat } from 'react-number-format';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';
import uniqid from 'uniqid';

/**
 * Internal dependencies
 */
import { PREFIX, SUFFIX, TICKET_LABELS } from '@moderntribe/tickets/data/blocks/ticket/constants';
import {Checkbox, DayPickerInput, LabeledItem} from '@moderntribe/common/elements';
import './style.pcss';
import {formatDate, parseDate} from "react-day-picker/moment";

class SalePrice extends PureComponent {
	static propTypes = {
		isDisabled: PropTypes.bool,
		currencyDecimalPoint: PropTypes.string,
		currencyNumberOfDecimals: PropTypes.number,
		currencyPosition: PropTypes.string,
		currencySymbol: PropTypes.string,
		currencyThousandsSep: PropTypes.string,
		minDefaultPrice: PropTypes.string,
		tempPrice: PropTypes.string,
		toggleSalePrice: PropTypes.func,
		salePriceChecked: PropTypes.bool,
		salePrice: PropTypes.string,
		updateSalePrice: PropTypes.func,
		dateFormat: PropTypes.string,
		fromDate: PropTypes.instanceOf(Date),
		toDate: PropTypes.instanceOf(Date),
		fromDateInput: PropTypes.string,
		toDateInput: PropTypes.string,
	};

	constructor( props ) {
		super( props );
		this.id = uniqid( 'ticket-sale-price' );
	}

	render() {
		const {
			isDisabled,
			currencyDecimalPoint,
			currencyNumberOfDecimals,
			currencyPosition,
			currencySymbol,
			currencyThousandsSep,
			minDefaultPrice,
			tempPrice,
			toggleSalePrice,
			salePriceChecked,
			salePrice,
			updateSalePrice,
			dateFormat,
			fromDate,
			toDate,
			fromDateInput,
			toDateInput,
		} = this.props;

		const numericFormatProps = {
			...( currencyPosition === PREFIX && { prefix: currencySymbol } ),
			...( currencyPosition === SUFFIX && { suffix: currencySymbol } ),
		};

		const handleChange = ( e ) => {
			if ( ! isNaN( e.value ) && e.value >= minDefaultPrice ) {
				updateSalePrice( e );
			}
		};

		const onDateChange = ( e ) => {

		}

		const FromDateProps = {
			value: fromDateInput,
			format: dateFormat,
			formatDate: formatDate,
			parseDate: parseDate,
			dayPickerProps: {
				selectedDays: [ fromDate, { from: fromDate, to: toDate } ],
				disabledDays: { after: toDate },
				modifiers: {
					start: fromDate,
					end: toDate,
				},
				toMonth: toDate,
			},
			onDayChange: onDateChange,
			inputProps: {
				disabled: isDisabled,
			},
		};

		const ToDateProps = {
			value: toDateInput,
			format: dateFormat,
			formatDate: formatDate,
			parseDate: parseDate,
			dayPickerProps: {
				selectedDays: [ fromDate, { from: fromDate, to: toDate } ],
				disabledDays: { before: fromDate },
				modifiers: {
					start: fromDate,
					end: toDate,
				},
				month: fromDate,
				fromMonth: fromDate,
			},
			onDayChange: onDateChange,
			inputProps: {
				disabled: isDisabled,
			},
		};

		return (
			<div className={"tribe-editor__ticket__sale-price-wrapper"}>
				<Checkbox
					className="tribe-editor__ticket__sale-price-checkbox"
					id={ this.id }
					// eslint-disable-next-line no-undef
					label="Add Sale Price"
					// eslint-disable-next-line no-undef
					aria-label="Add Sale Price"
					checked={salePriceChecked}
					onChange={toggleSalePrice}
					value={salePriceChecked}
				/>
				{salePriceChecked && (
					<div className={"tribe-editor__ticket__sale-price--fields"}>
						<NumericFormat
							allowNegative={false}
							className="tribe-editor__input tribe-editor__ticket__price-input"
							decimalScale={currencyNumberOfDecimals}
							decimalSeparator={currencyDecimalPoint}
							displayType="input"
							fixedDecimalScale={true}
							{...numericFormatProps}
							onValueChange={handleChange}
							thousandSeparator={currencyThousandsSep}
							value={salePrice}
						/>
						<div className={"tribe-editor__ticket__sale-price--dates"}>
							<div className={"tribe-editor__ticket__sale-price--start-date"}>
								<DayPickerInput { ...FromDateProps }/>
							</div>
							<div className={"tribe-editor__ticket__sale-price--end-date"}>
								<DayPickerInput { ...ToDateProps }/>
							</div>
						</div>
					</div>
				)}
			</div>
		);
	}
}

export default SalePrice;
