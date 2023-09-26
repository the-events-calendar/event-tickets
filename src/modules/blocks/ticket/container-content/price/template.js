/**
 * External dependencies
 */
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';
import uniqid from 'uniqid';

/**
 * Internal dependencies
 */
import { Input } from '@moderntribe/common/elements';
import { LabeledItem } from '@moderntribe/common/elements';
import './style.pcss';

class Price extends PureComponent {
	static propTypes = {
		isDisabled: PropTypes.bool,
		onTempPriceChange: PropTypes.func.isRequired,
		tempPrice: PropTypes.string,
	};

	constructor( props ) {
		super( props );
		this.id = uniqid( 'ticket-price' );
	}

	render() {
		const {
			isDisabled,
			onTempPriceChange,
			tempPrice,
			currencyPosition,
			currencySymbol,
		} = this.props;

		return (
			<div className={ classNames(
				'tribe-editor__ticket__price',
				'tribe-editor__ticket__content-row',
				'tribe-editor__ticket__content-row--price',
			) }>
				<LabeledItem
					className="tribe-editor__ticket__price-label"
					forId={ this.id }
					isLabel={ true }
					label={ __( 'Ticket price', 'event-tickets' ) }
				/>

				<Input
					id={ this.id }
					className="tribe-editor__ticket__price-input"
					value={ tempPrice }
					onChange={ onTempPriceChange }
					disabled={ isDisabled }
					type="number"
					min="0"
				/>
			</div>
		);
	}
}

export default Price;
