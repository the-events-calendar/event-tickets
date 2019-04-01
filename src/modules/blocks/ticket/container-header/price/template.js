/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';
import AutosizeInput from 'react-input-autosize';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SUFFIX, PRICE_POSITIONS } from '@moderntribe/tickets/data/blocks/ticket/constants';
import './style.pcss';

const TicketContainerHeaderPriceInput = ( {
	isDisabled,
	currencyPosition,
	currencySymbol,
	onTempPriceChange,
	tempPrice,
} ) => {
	const priceInput = (
		<Fragment>
			<span className="tribe-editor__ticket__container-header-price-currency">
				{ currencySymbol }
			</span>
			<AutosizeInput
				className="tribe-editor__ticket__container-header-price-input"
				value={ tempPrice }
				placeholder={ __( '0', 'event-tickets' ) }
				onChange={ onTempPriceChange }
				disabled={ isDisabled }
				type="number"
				min="0"
			/>
		</Fragment>
	);

	return currencyPosition === SUFFIX ? priceInput.reverse() : priceInput;
};

TicketContainerHeaderPriceInput.propTypes = {
	isDisabled: PropTypes.bool,
	currencyPosition: PropTypes.oneOf( PRICE_POSITIONS ),
	currencySymbol: PropTypes.string,
	onTempPriceChange: PropTypes.func,
	tempPrice: PropTypes.string,
};

const TicketContainerHeaderPriceLabel = ( {
	currencyPosition,
	currencySymbol,
	price,
} ) => {
	const priceLabel = (
		<Fragment>
			<span className="tribe-editor__ticket__container-header-price-currency">
				{ currencySymbol }
			</span>
			<span className="tribe-editor__ticket__container-header-price-value">
				{ price }
			</span>
		</Fragment>
	);

	return currencyPosition === SUFFIX ? priceLabel.reverse() : priceLabel;
};

TicketContainerHeaderPriceLabel.propTypes = {
	currencyPosition: PropTypes.oneOf( PRICE_POSITIONS ),
	currencySymbol: PropTypes.string,
	price: PropTypes.string,
};

const TicketContainerHeaderPrice = ( {
	isDisabled,
	isSelected,
	currencyPosition,
	currencySymbol,
	onTempPriceChange,
	tempPrice,
	price,
} ) => (
	<div className="tribe-editor__ticket__container-header-price">
		{ isSelected
			? (
				<TicketContainerHeaderPriceInput
					currencyPosition={ currencyPosition }
					currencySymbol={ currencySymbol }
					onTempPriceChange={ onTempPriceChange }
					tempPrice={ tempPrice }
					isDisabled={ isDisabled }
				/>
			)
			: (
				<TicketContainerHeaderPriceLabel
					currencyPosition={ currencyPosition }
					currencySymbol={ currencySymbol }
					price={ price }
				/>
			)
		}
	</div>
);

TicketContainerHeaderPrice.propTypes = {
	isDisabled: PropTypes.bool,
	isSelected: PropTypes.bool,
	currencyPosition: PropTypes.oneOf( PRICE_POSITIONS ),
	currencySymbol: PropTypes.string,
	onTempPriceChange: PropTypes.func,
	tempPrice: PropTypes.string,
	price: PropTypes.string,
};

export default TicketContainerHeaderPrice;
