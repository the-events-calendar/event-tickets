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

const getHeaderElement = ( header, description ) => {
	if ( ! description ) {
		return ( <div className="tickets-heading tickets-row-line">{ header }</div> );
	}

	return (
		<div className="tickets-heading__wrapper tickets-row-line">
			<div className="tickets-heading tickets-heading__title">{ header }</div>
			<div className="tickets-heading__description">{ description }</div>
		</div>
	);
};

const Card = ( { className, children, header, description } ) => {
	return (
		<div className={ classNames( 'tribe-editor__card', className ) }>
			{ header && getHeaderElement( header, description ) }
			{ children }
		</div>
	);
};

Card.propTypes = {
	className: PropTypes.string,
	children: PropTypes.node,
	header: PropTypes.string,
	description: PropTypes.string,
};

export default Card;
