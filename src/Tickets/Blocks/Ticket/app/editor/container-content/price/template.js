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
import { LabeledItem } from '@moderntribe/common/elements';
import './style.pcss';
import SalePrice from "../sale-price/container";

class Price extends PureComponent {
	static propTypes = {
		currencyDecimalPoint: PropTypes.string,
		currencyNumberOfDecimals: PropTypes.number,
		currencyPosition: PropTypes.string,
		currencySymbol: PropTypes.string,
		currencyThousandsSep: PropTypes.string,
		isDisabled: PropTypes.bool,
		minDefaultPrice: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		onTempPriceChange: PropTypes.func.isRequired,
		tempPrice: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		showSalePrice: PropTypes.bool,
		clientId: PropTypes.string,
	};

	constructor( props ) {
		super( props );
		this.id = uniqid( 'ticket-price' );
	}

	render() {
		const {
			currencyDecimalPoint,
			currencyNumberOfDecimals,
			currencyPosition,
			currencySymbol,
			currencyThousandsSep,
			isDisabled,
			minDefaultPrice,
			onTempPriceChange,
			tempPrice,
			showSalePrice,
			clientId,
		} = this.props;

		const numericFormatProps = {
			...( currencyPosition === PREFIX && { prefix: currencySymbol } ),
			...( currencyPosition === SUFFIX && { suffix: currencySymbol } ),
		};

		const handleChange = ( e ) => {
			if ( ! isNaN( e.value ) && e.value >= minDefaultPrice ) {
				onTempPriceChange( e );
			}
		};

		return (
			<div className={ classNames(
				'tribe-editor__ticket__price',
				'tribe-editor__ticket__content-row',
				'tribe-editor__ticket__content-row--price',
			) }>
				<div className={"tribe-editor__ticket__price-wrapper"}>
					<LabeledItem
						className="tribe-editor__ticket__price-label"
						forId={ this.id }
						isLabel={ true }
						// eslint-disable-next-line no-undef
						label={sprintf(
							/* Translators: %s - the singular label for a ticket. */
							__('%s price', 'event-tickets'),
							TICKET_LABELS.ticket.singular
						)}
					/>

					<NumericFormat
						allowNegative={ false }
						className="tribe-editor__input tribe-editor__ticket__price-input"
						decimalScale={ currencyNumberOfDecimals }
						decimalSeparator={ currencyDecimalPoint }
						disabled={ isDisabled }
						displayType="input"
						fixedDecimalScale={ true }
						{ ...numericFormatProps }
						onValueChange={ handleChange }
						thousandSeparator={ currencyThousandsSep }
						value={ tempPrice }
					/>
				</div>
				{ showSalePrice && <SalePrice clientId={clientId} /> }
			</div>
		);
	}
}

export default Price;
