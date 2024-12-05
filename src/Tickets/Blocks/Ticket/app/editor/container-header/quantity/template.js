/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Wordpress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { IconWithTooltip } from '@moderntribe/tickets/elements';
import QuantityBar from './quantity-bar/element';
import './style.pcss';

const TicketContainerHeaderDescription = ( {
	isDisabled,
	isSelected,
	isShared,
	isUnlimited,
	sold = 0,
	capacity = 0,
	sharedSold,
	sharedCapacity = 0,
} ) => {
	const total = isShared ? sharedCapacity : capacity;

	const getLabel = () => sprintf( __( '%d sold', 'event-tickets' ), sold );

	/* eslint-disable max-len */
	const getQuantityBar = () => (
		isUnlimited
			? (
				<span className="tribe-editor__ticket__container-header-quantity-unlimited tribe-editor__ticket__container-header-label">
					{ __( 'unlimited', 'event-tickets' ) }
				</span>
			)
			: (
				<QuantityBar
					sold={ sold }
					sharedSold={ sharedSold }
					capacity={ capacity }
					total={ total }
					isDisabled={ isDisabled }
				/>
			)
	);
	/* eslint-enable max-len */

	return ! isSelected && (
		<div className="tribe-editor__ticket__container-header-quantity">
			<span className="tribe-editor__ticket__container-header-quantity-label">
				{ getLabel() }
				<IconWithTooltip
					/* eslint-disable-next-line max-len */
					propertyName={ __( 'This pertains to Orders that have been marked Completed.', 'event-tickets' ) }
					icon={ <span className="dashicons dashicons-info-outline" /> }
				/>
			</span>
			{ getQuantityBar() }
		</div>
	);
};

TicketContainerHeaderDescription.propTypes = {
	isDisabled: PropTypes.bool,
	isSelected: PropTypes.bool,
	isShared: PropTypes.bool,
	isUnlimited: PropTypes.bool,
	sold: PropTypes.number,
	capacity: PropTypes.number,
	sharedSold: PropTypes.number,
	sharedCapacity: PropTypes.number,
};

export default TicketContainerHeaderDescription;
