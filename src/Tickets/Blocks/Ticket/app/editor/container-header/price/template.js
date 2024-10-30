/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';
import { NumericFormat } from 'react-number-format';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PREFIX, SUFFIX, PRICE_POSITIONS } from '@moderntribe/tickets/data/blocks/ticket/constants';
import './style.pcss';

const TicketContainerHeaderPriceLabel = ( {
	available,
	currencyDecimalPoint,
	currencyNumberOfDecimals,
	currencyThousandsSep,
	currencyPosition,
	currencySymbol,
	isUnlimited,
	price,
	showSalePrice,
	salePrice,
	onSale,
} ) => {
	const getAvailableLabel = () => (
		isUnlimited
			? __( 'unlimited', 'event-tickets' )
			: (
				<>
					<span className="tribe-editor__ticket__container-header-label__available">
						{ available }
					</span>
					{ __( 'available', 'event-tickets' ) }
				</>
			)
	);

	const numericFormatProps = {
		...( currencyPosition === PREFIX && { prefix: currencySymbol } ),
		...( currencyPosition === SUFFIX && { suffix: currencySymbol } ),
	};

	/**
	 * Check if the ticket is on sale and the sale price is valid to be displayed.
	 */
	const hasValidSalePrice = onSale && showSalePrice && salePrice !== '';

	/**
	 * The price class to be used.
	 */
	const priceClass = hasValidSalePrice ? 'tribe-editor__ticket__container-header-price__price--on-sale' : 'tribe-editor__ticket__container-header-price__price';

	return (
		<Fragment>
			<NumericFormat
				className={ priceClass }
				allowNegative={ false }
				decimalScale={ currencyNumberOfDecimals }
				decimalSeparator={ currencyDecimalPoint }
				displayType="text"
				fixedDecimalScale={ true }
				{ ...numericFormatProps }
				thousandSeparator={ currencyThousandsSep }
				value={ price }
			/>
			{ hasValidSalePrice && (
				<NumericFormat
					className={ 'tribe-editor__ticket__container-header-price__sale-price' }
					allowNegative={ false }
					decimalScale={ currencyNumberOfDecimals }
					decimalSeparator={ currencyDecimalPoint }
					displayType="text"
					fixedDecimalScale={ true }
					{ ...numericFormatProps }
					thousandSeparator={ currencyThousandsSep }
					value={ salePrice }
				/>
			) }
			<div className="tribe-editor__ticket__container-header-label">
				{ getAvailableLabel() }
			</div>
		</Fragment>
	);
};

TicketContainerHeaderPriceLabel.propTypes = {
	available: PropTypes.number,
	currencyDecimalPoint: PropTypes.string,
	currencyNumberOfDecimals: PropTypes.number,
	currencyPosition: PropTypes.oneOf( PRICE_POSITIONS ),
	currencySymbol: PropTypes.string,
	currencyThousandsSep: PropTypes.string,
	isUnlimited: PropTypes.bool,
	price: PropTypes.string,
	showSalePrice: PropTypes.bool,
	salePrice: PropTypes.string,
	onSale: PropTypes.bool,
};

const TicketContainerHeaderPrice = ( {
	available,
	currencyDecimalPoint,
	currencyNumberOfDecimals,
	currencyPosition,
	currencySymbol,
	currencyThousandsSep,
	isUnlimited,
	price,
	showSalePrice,
	salePrice,
	onSale,
} ) => (
	<div className="tribe-editor__ticket__container-header-price">
		<TicketContainerHeaderPriceLabel
			available={ available }
			currencyDecimalPoint={ currencyDecimalPoint }
			currencyNumberOfDecimals={ currencyNumberOfDecimals }
			currencyPosition={ currencyPosition }
			currencySymbol={ currencySymbol }
			currencyThousandsSep={ currencyThousandsSep }
			isUnlimited={ isUnlimited }
			price={ price }
			showSalePrice={ showSalePrice }
			salePrice={ salePrice }
			onSale={ onSale }
		/>
	</div>
);

TicketContainerHeaderPrice.propTypes = {
	available: PropTypes.number,
	currencyDecimalPoint: PropTypes.string,
	currencyNumberOfDecimals: PropTypes.number,
	currencyPosition: PropTypes.oneOf( PRICE_POSITIONS ),
	currencySymbol: PropTypes.string,
	currencyThousandsSep: PropTypes.string,
	isDisabled: PropTypes.bool,
	isSelected: PropTypes.bool,
	isUnlimited: PropTypes.bool,
	onTempPriceChange: PropTypes.func,
	price: PropTypes.string,
	tempPrice: PropTypes.string,
	showSalePrice: PropTypes.bool,
	salePrice: PropTypes.string,
	onSale: PropTypes.bool,
};

export default TicketContainerHeaderPrice;
