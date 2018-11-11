/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { sprintf } from 'sprintf-js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Pencil } from '@moderntribe/common/icons';
import QuantityBar from './quantity-bar/element';
import { Button } from '@moderntribe/common/elements';
import './style.pcss';

/**
 * @todo render and handle the next possible items:
 *
 * - has attendees information use Clipboard icon: <button className="tribe-editor__btn--label"><Clipboard /></button>
 */

const TicketDisplay = ( props ) => {
	const {
		title,
		description,
		price,
		currencySymbol,
		currencyPosition,
		isShared,
		isUnlimited,
		capacity,
		editBlock,
		sold,
		sharedSold,
		sharedCapacity,
		isSelected,
		isTicketDisabled,
	} = props;

	const total = isShared ? sharedCapacity : capacity;
	const priceLabel = [ currencySymbol, price ];
	const labels = {
		unlimited: sprintf( __( '%d sold', 'events-gutenberg' ), sold ),
		normal: sprintf( __( '%d of %d sold', 'events-gutenberg' ), sold, total ),
	};

	const quantityBar = isUnlimited
		? (
			<span className="tribe-editor__quantity--unlimited">
				{ __( 'unlimited', 'events-gutenberg' ) }
			</span>
		)
		: (
			<QuantityBar
				sold={ sold }
				sharedSold={ sharedSold }
				capacity={ capacity }
				total={ total }
				isDisabled={ isTicketDisabled }
			/>
		);

	const editIcon = ! isTicketDisabled && isSelected && (
		<Button className="tribe-editor__btn--label" onClick={ editBlock }><Pencil /></Button>
	);

	return (
		<div className={ classNames(
			'tribe-editor__ticket-display-header',
			{
				'tribe-editor__ticket-display-header--disabled': isTicketDisabled,
			},
		) }>
			<div className="tribe-editor__ticket-content">
				<div className="tribe-editor__ticket-title-container">
					<h3 className="tribe-editor__ticket-title">{ title }</h3>
					{ editIcon }
				</div>
				{ description && <div className="tribe-editor__ticket-description">{ description }</div> }
			</div>
			<div className="tribe-editor__ticket-price">
				{ 'suffix' === currencyPosition ? [ ...priceLabel ].reverse() : priceLabel }
			</div>
			<div className="tribe-editor__ticket-quantity">
				<span className="tribe-editor__quantity-label">
					{ isUnlimited ? labels.unlimited : labels.normal }
				</span>
				{ quantityBar }
			</div>
		</div>
	);
};

TicketDisplay.propTypes = {
	title: PropTypes.string.isRequired,
	description: PropTypes.string,
	price: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] ),
	currencySymbol: PropTypes.string,
	currencyPosition: PropTypes.oneOf( [ 'prefix', 'suffix' ] ),
	isShared: PropTypes.bool,
	isUnlimited: PropTypes.bool,
	capacity: PropTypes.number,
	sold: PropTypes.number,
	sharedSold: PropTypes.number,
	sharedCapacity: PropTypes.number,
	editBlock: PropTypes.func,
	isSelected: PropTypes.bool,
	isTicketDisabled: PropTypes.bool,
};

/**
 * @todo Populate the values like currency sign and position from the event date  time block
 */

TicketDisplay.defaultProps = {
	title: '',
	description: '',
	price: 0,
	currencySymbol: '$',
	currencyPosition: 'prefix',
	isUnlimited: false,
	capacity: 0,
};

export default TicketDisplay;
