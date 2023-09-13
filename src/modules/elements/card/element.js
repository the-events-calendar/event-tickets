/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './style.pcss';

const Card = ( {
	className,
	children,
} ) => {

	return (
		<div
			className={ classNames(
				'tribe-editor__card',
				className,
			) }
		>
			{ children }
		</div>
	);
};

Card.propTypes = {
	className: PropTypes.string,
	children: PropTypes.node,
	header: PropTypes.node,
};

export default Card;
