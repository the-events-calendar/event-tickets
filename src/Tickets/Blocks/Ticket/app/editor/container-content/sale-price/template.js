/**
 * External dependencies
 */
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { NumericFormat } from 'react-number-format';
import { __ } from '@wordpress/i18n';
import { formatDate, parse as parseDate } from 'date-fns';

/**
 * Wordpress dependencies
 */
import uniqid from 'uniqid';

/**
 * Internal dependencies
 */
import { PREFIX, SUFFIX, SALE_PRICE_LABELS, WOO_CLASS } from '@moderntribe/tickets/data/blocks/ticket/constants';
import { Checkbox, DayPickerInput, LabeledItem } from '@moderntribe/common/elements';
import { getTicketsProvider } from '@moderntribe/tickets/data/blocks/ticket/selectors';
import './style.pcss';
import {DateTimeRangePicker} from "@moderntribe/tickets/elements";

/**
 * Get the ticket provider from the common store.
 *
 * @since 5.19.1
 * @return {string} The ticket provider.
 */
const getTicketProviderFromCommon = () => {
	return getTicketsProvider( window.__tribe_common_store__.getState() );
};

/**
 * SalePrice component.
 *
 * @since 5.9.0
 */
class SalePrice extends PureComponent {
	static propTypes = {
		isDisabled: PropTypes.bool,
		currencyDecimalPoint: PropTypes.string,
		currencyNumberOfDecimals: PropTypes.number,
		currencyPosition: PropTypes.string,
		currencySymbol: PropTypes.string,
		currencyThousandsSep: PropTypes.string,
		minDefaultPrice: PropTypes.oneOfType([ PropTypes.string, PropTypes.number]),
		tempPrice: PropTypes.string,
		toggleSalePrice: PropTypes.func,
		salePriceChecked: PropTypes.bool,
		salePrice: PropTypes.string,
		updateSalePrice: PropTypes.func,
		dateFormat: PropTypes.string,
		fromDate: PropTypes.oneOfType([
			PropTypes.instanceOf(Date),
			PropTypes.oneOf([''])
		]),
		toDate: PropTypes.oneOfType([
			PropTypes.instanceOf(Date),
			PropTypes.oneOf([''])
		]),
		fromDateInput: PropTypes.string,
		toDateInput: PropTypes.string,
		onFromDateChange: PropTypes.func,
		onToDateChange: PropTypes.func,
		validSalePrice: PropTypes.bool,
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
			salePriceChecked = false,
			salePrice,
			updateSalePrice,
			dateFormat,
			fromDate,
			toDate,
			fromDateInput,
			toDateInput,
			onFromDateChange,
			onToDateChange,
			validSalePrice,
		} = this.props;

		/**
		 * Props to pass to the NumericFormat component
		 */
		const numericFormatProps = {
			...( currencyPosition === PREFIX && { prefix: currencySymbol } ),
			...( currencyPosition === SUFFIX && { suffix: currencySymbol } ),
		};

		/**
		 * Handles the change of the sale price.
		 * @param e The event.
		 */
		const handleChange = ( e ) => {
			if ( ! isNaN( e.value ) && e.value >= minDefaultPrice ) {
				updateSalePrice( e );
			}
		};

		/**
		 * The sale price classes.
		 */
		const salPriceClasses = classNames(
			'tribe-editor__input tribe-editor__ticket__sale-price-input',
			{ 'tribe-editor__ticket__sale-price--error': !validSalePrice }
		);

		// Check if the provider is WooCommerce.
		const isWoo = getTicketProviderFromCommon() === WOO_CLASS;

		/**
		 * Props for the FromDate input.
		 */
		const FromDateProps = {
			value: fromDateInput,
			format: dateFormat,
			formatDate: formatDate,
			parseDate: parseDate,
			placeholder: dateFormat,
			dayPickerProps: {
				selectedDays: [ fromDate, { from: fromDate, to: toDate } ],
				disabledDays: { after: toDate },
				modifiers: {
					start: fromDate,
					end: toDate,
				},
				toMonth: toDate,
			},
			onDayChange: onFromDateChange,
			inputProps: {
				disabled: isDisabled,
			},
		};

		/**
		 * Props for the ToDate input.
		 */
		const ToDateProps = {
			value: toDateInput,
			format: dateFormat,
			formatDate: formatDate,
			parseDate: parseDate,
			placeholder: dateFormat,
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
			onDayChange: onToDateChange,
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
					label={ SALE_PRICE_LABELS.add_sale_price }
					// eslint-disable-next-line no-undef
					aria-label={ SALE_PRICE_LABELS.add_sale_price }
					checked={ ! isWoo && salePriceChecked }
					onChange={toggleSalePrice}
					value={salePriceChecked ? '1' : '0'}
					disabled={isDisabled}
				/>
				{ ! isWoo && salePriceChecked && (
					<div className={"tribe-editor__ticket__sale-price--fields"}>
						<div className={"tribe-editor__ticket__sale-price__input-wrapper"}>
							<LabeledItem
								className="tribe-editor__ticket__sale-price--label"
								label={ SALE_PRICE_LABELS.sale_price_label }
							/>
							<NumericFormat
								allowNegative={false}
								className={salPriceClasses}
								decimalScale={currencyNumberOfDecimals}
								decimalSeparator={currencyDecimalPoint}
								displayType="input"
								fixedDecimalScale={true}
								{...numericFormatProps}
								onValueChange={handleChange}
								thousandSeparator={currencyThousandsSep}
								value={salePrice}
								disabled={isDisabled}
							/>
						</div>
						{ ! validSalePrice && (
							<div className={'tribe-editor__ticket__sale-price__error-message'}>
								{ SALE_PRICE_LABELS.invalid_price }
							</div>
						) }
						<div className={"tribe-editor__ticket__sale-price--dates"}>
							<LabeledItem
								className="tribe-editor__ticket__sale-price__dates--label"
								label={ SALE_PRICE_LABELS.on_sale_from }
							/>
							<div className={"tribe-editor__ticket__sale-price--start-date"}>
								<DayPickerInput { ...FromDateProps }/>
							</div>
							<span>
								{ SALE_PRICE_LABELS.to }
							</span>
							<div className={"tribe-editor__ticket__sale-price--end-date"}>
								<DayPickerInput { ...ToDateProps }/>
							</div>
						</div>
					</div>
				)}

				{ isWoo && (
					<div className={'tribe-editor__ticket__sale-price__error-message'}>
						<p>
							{ __( 'The sale price can be managed via WooCommerce\'s product editor.', 'event-tickets' ) }
						</p>
					</div>
				) }
			</div>
		);
	}
}

export default SalePrice;
