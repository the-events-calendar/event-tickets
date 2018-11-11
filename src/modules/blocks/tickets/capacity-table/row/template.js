/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */

const CapacityRow = ( { label, items, right } ) => (
	<div className="tribe-editor__capacity-row">
		<span className="tribe-editor__capacity-row--left">
			{ label && <span className="tribe-editor__capacity-row__label">{ label }</span> }
			{ items && <span className="tribe-editor__capacity-row__items">{ items }</span> }
		</span>
		<span className="tribe-editor__capacity-row--right">{ right }</span>
	</div>
);

CapacityRow.propTypes = {
	label: PropTypes.string,
	items: PropTypes.string,
	right: PropTypes.node,
};

CapacityRow.defaultProps = {
	label: '',
	items: '',
	right: '',
};

export default CapacityRow;
