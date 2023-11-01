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
	header,
} ) => {
	return (
		<div
			className={ classNames(
				'tribe-editor__card',
				className,
			) }
		>
			{ header && ( <div className="tickets-heading tickets-row-line">{ header }</div> ) }
			{ children }
		</div>
	);
};

Card.propTypes = {
	className: PropTypes.string,
	children: PropTypes.node,
	header: PropTypes.string,
};

export default Card;
