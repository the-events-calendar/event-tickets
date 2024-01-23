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

	return (
		<Fragment>
			<NumericFormat
				allowNegative={ false }
				decimalScale={ currencyNumberOfDecimals }
				decimalSeparator={ currencyDecimalPoint }
				displayType="text"
				fixedDecimalScale={ true }
				{ ...numericFormatProps }
				thousandSeparator={ currencyThousandsSep }
				value={ price }
			/>
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
};

export default TicketContainerHeaderPrice;
