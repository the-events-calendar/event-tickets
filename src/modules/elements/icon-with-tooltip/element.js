/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { Tooltip } from '@wordpress/components';

const IconWithTooltip = ( {
	description,
	icon,
	position,
	propertyName,
} ) => {
	const text = (
		<div>
			{ propertyName }
			{ description && ': ' }
			{ description && <em>{description}</em> }
		</div>
	);

	return (
		<Tooltip
			text={ text }
			position={ position }
		>
			<span>{ icon }</span>
		</Tooltip>
	);
};

IconWithTooltip.defaultProps = {
	description: '',
	position: 'top right',
};

IconWithTooltip.propTypes = {
	description: PropTypes.string,
	icon: PropTypes.node,
	position: PropTypes.oneOf( [
		'top left',
		'top center',
		'top right',
		'bottom left',
		'bottom center',
		'bottom right',
	] ),
	propertyName: PropTypes.string,
};

export default IconWithTooltip;
