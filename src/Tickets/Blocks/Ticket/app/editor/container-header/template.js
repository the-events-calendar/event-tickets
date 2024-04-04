/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import TicketContainerHeaderTitle from './title/container';
import TicketContainerHeaderDescription from './description/container';
import TicketContainerHeaderPrice from './price/container';
import TicketContainerHeaderQuantity from './quantity/container';
import './style.pcss';

const TicketContainerHeader = ( {
	clientId,
	isSelected,
	isOnSale,
} ) => {
	if ( isSelected ) {
		return null;
	}

	function OnSaleBug( onSale ) {
		if ( ! onSale ) {
			return null;
		}
		return (
			<div className="tribe-editor__ticket__container-header__sale-label-container">
				<span className="tribe-editor__ticket__container-header__sale-label">On Sale</span>
			</div>
		);
	}

	return (
		<Fragment>
			<div className="tribe-editor__ticket__container-header-details">
				{ OnSaleBug( isOnSale ) }
				<TicketContainerHeaderTitle clientId={ clientId } isSelected={ isSelected } />
				<TicketContainerHeaderDescription clientId={ clientId } isSelected={ isSelected } />
			</div>
			<TicketContainerHeaderPrice clientId={ clientId } isSelected={ isSelected } />
			<TicketContainerHeaderQuantity clientId={ clientId } isSelected={ isSelected } />
		</Fragment>
	);
};

TicketContainerHeader.propTypes = {
	clientId: PropTypes.string,
	isSelected: PropTypes.bool,
	isOnSale: PropTypes.bool,
};

export default TicketContainerHeader;
