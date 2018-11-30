/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TicketContainerHeaderTitle from './title/container';
import TicketContainerHeaderDescription from './description/container';
import TicketContainerHeaderPrice from './price/container';
import TicketContainerHeaderQuantity from './quantity/container';
import './style.pcss';

const TicketContainerHeader = ( {
	blockId,
	isSelected,
} ) => {
	return (
		<Fragment>
			<div className="tribe-editor__ticket__container-header-details">
				<TicketContainerHeaderTitle blockId={ blockId } isSelected={ isSelected } />
				<TicketContainerHeaderDescription blockId={ blockId } isSelected={ isSelected } />
			</div>
			<TicketContainerHeaderPrice blockId={ blockId } isSelected={ isSelected } />
			<TicketContainerHeaderQuantity blockId={ blockId } isSelected={ isSelected } />
		</Fragment>
	);
};

TicketContainerHeader.propTypes = {
	blockId: PropTypes.string,
	isSelected: PropTypes.bool,
};

export default TicketContainerHeader;
