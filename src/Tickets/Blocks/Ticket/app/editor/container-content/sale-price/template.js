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
import {Checkbox, LabeledItem} from '@moderntribe/common/elements';
import './style.pcss';

class SalePrice extends PureComponent {
	static propTypes = {
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
	};

	constructor( props ) {
		super( props );
		this.id = uniqid( 'ticket-sale-price' );
	}

	render() {
		const {
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
				)}
			</div>
		);
	}
}

export default SalePrice;
