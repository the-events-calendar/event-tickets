/**
 * External dependencies
 */
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import AutosizeInput from 'react-input-autosize';
import uniqid from 'uniqid';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.pcss';
import { sendValue } from '@moderntribe/common/utils/input';
import { getDefaultProviderCurrency } from '@moderntribe/tickets/data/utils';

class Header extends PureComponent {
	static PRICE_POSITIONS = {
		suffix: 'suffix',
		prefix: 'prefix',
	};

	static propTypes = {
		title: PropTypes.string,
		titlePlaceholder: PropTypes.string,
		setTitle: PropTypes.func.isRequired,
		description: PropTypes.string,
		descriptionPlaceholder: PropTypes.string,
		setDescription: PropTypes.func.isRequired,
		price: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] ),
		currencySymbol: PropTypes.string,
		pricePosition: PropTypes.oneOf( Object.keys( Header.PRICE_POSITIONS ) ),
		pricePlaceholder: PropTypes.string,
		setPrice: PropTypes.func.isRequired,
	};


	static defaultProps = {
		title: '',
		titlePlaceholder: __( 'Ticket Type', 'events-gutenberg' ),
		description: '',
		descriptionPlaceholder: __( 'Description', 'events-gutenberg' ),
		price: 0,
		pricePlaceholder: __( '0', 'events-gutenberg' ),
		currencySymbol: getDefaultProviderCurrency(),
		pricePosition: Header.PRICE_POSITIONS.prefix,
	};

	constructor( props ) {
		super( props );
		this.ids = {
			price: uniqid( 'ticket-creation-price-' ),
			title: uniqid( 'ticket-creation-title-' ),
			description: uniqid( 'ticket-creation-description-' ),
		};
	}

	renderPriceInput() {
		const { price, pricePlaceholder, setPrice } = this.props;

		return (
			<AutosizeInput
				key="price-input"
				id={ this.ids.price }
				name="ticket-creation-description"
				className="tribe-editor__new-ticket__description"
				value={ price }
				placeholder={ pricePlaceholder }
				onChange={ sendValue( setPrice ) }
				type="number"
				min="0"
			/>
		);
	}

	renderPriceLabel() {
		const { currencySymbol } = this.props;
		return [ <span key="price-currency">{ currencySymbol }</span>, this.renderPriceInput() ];
	}

	renderPrice() {
		const { pricePosition } = this.props;
		return pricePosition === Header.PRICE_POSITIONS.prefix
			? this.renderPriceLabel()
			: [ ...this.renderPriceLabel() ].reverse();
	}

	render() {
		const {
			title,
			titlePlaceholder,
			setTitle,
			description,
			descriptionPlaceholder,
			setDescription,
		} = this.props;

		return (
			<div className="tribe-editor__ticket-container__header">
				<div className="tribe-editor__ticket-container__header-content">
					<AutosizeInput
						id={ this.ids.title }
						name="ticket-creation-title"
						className="tribe-editor__ticket-container__header-title"
						value={ title }
						placeholder={ titlePlaceholder }
						onChange={ sendValue( setTitle ) }
					/>
					<AutosizeInput
						id={ this.ids.description }
						name="ticket-creation-description"
						className="tribe-editor__ticket-container__header-description"
						value={ description }
						placeholder={ descriptionPlaceholder }
						onChange={ sendValue( setDescription ) }
					/>
				</div>
				<div className="tribe-editor__ticket-container__header-price">
					{ this.renderPrice() }
				</div>
			</div>
		);
	}
}

export default Header;
