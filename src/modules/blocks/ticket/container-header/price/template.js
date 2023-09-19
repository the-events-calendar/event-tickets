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
import { PREFIX, SUFFIX, PRICE_POSITIONS } from '@moderntribe/tickets/data/blocks/ticket/constants';
import './style.pcss';

const TicketContainerHeaderPriceInput = ( {
	isDisabled,
	currencyPosition,
	currencySymbol,
	onTempPriceChange,
	tempPrice,
} ) => {
	return (
		<Fragment>
			{ currencyPosition === PREFIX && (
				<span className="tribe-editor__ticket__container-header-price-currency">
					{ currencySymbol }
				</span>
			) }
			<AutosizeInput
				className="tribe-editor__ticket__container-header-price-input"
				value={ tempPrice }
				placeholder={ __( '0', 'event-tickets' ) }
				onChange={ onTempPriceChange }
				disabled={ isDisabled }
				type="number"
				min="0"
			/>
			{ currencyPosition === SUFFIX && (
				<span className="tribe-editor__ticket__container-header-price-currency">
					{ currencySymbol }
				</span>
			) }
		</Fragment>
	);
};

TicketContainerHeaderPriceInput.propTypes = {
	isDisabled: PropTypes.bool,
	currencyPosition: PropTypes.oneOf( PRICE_POSITIONS ),
	currencySymbol: PropTypes.string,
	onTempPriceChange: PropTypes.func,
	tempPrice: PropTypes.string,
};

const TicketContainerHeaderPriceLabel = ( {
	available,
	currencyPosition,
	currencySymbol,
	price,
	isUnlimited,
} ) => {
	const getAvailableLabel = () => (
		isUnlimited
		? __( 'unlimited', 'event-tickets' )
		: `${available} ${ __( 'available', 'event-tickets' ) }`
	)

	return (
		<Fragment>
			{ currencyPosition === PREFIX && (
				<span className="tribe-editor__ticket__container-header-price-currency">
					{ currencySymbol }
				</span>
			) }
			<span className="tribe-editor__ticket__container-header-price-value">
				{ price }
			</span>
			{ currencyPosition === SUFFIX && (
				<span className="tribe-editor__ticket__container-header-price-currency">
					{ currencySymbol }
				</span>
			) }
			<div className="tribe-editor__ticket__container-header-label">
				{ getAvailableLabel() }
			</div>
		</Fragment>
	);
};

TicketContainerHeaderPriceLabel.propTypes = {
	available: PropTypes.number,
	currencyPosition: PropTypes.oneOf( PRICE_POSITIONS ),
	currencySymbol: PropTypes.string,
	price: PropTypes.string,
};

const TicketContainerHeaderPrice = ( {
	available,
	isDisabled,
	isSelected,
	isUnlimited,
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
					available={ available }
					currencyPosition={ currencyPosition }
					currencySymbol={ currencySymbol }
					price={ price }
					isUnlimited={ isUnlimited }
				/>
			)
		}
	</div>
);

TicketContainerHeaderPrice.propTypes = {
	available: PropTypes.number,
	currencyPosition: PropTypes.oneOf( PRICE_POSITIONS ),
	currencySymbol: PropTypes.string,
	isDisabled: PropTypes.bool,
	isSelected: PropTypes.bool,
	isUnlimited: PropTypes.bool,
	onTempPriceChange: PropTypes.func,
	price: PropTypes.string,
	tempPrice: PropTypes.string,
};

export default TicketContainerHeaderPrice;
