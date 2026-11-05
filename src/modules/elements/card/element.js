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
		return <div className="tickets-heading tickets-row-line">{ header }</div>;
	}

	return (
		<div className="tickets-heading__wrapper tickets-row-line">
			<div className="tickets-heading tickets-heading__title">{ header }</div>
			<div className="tickets-heading__description">{ description }</div>
		</div>
	);
};

const getHeaderWithActions = ( header, description, actions ) => (
	<div className="tickets-heading__row tickets-row-line">
		<div className="tickets-heading__text">
			<div className="tickets-heading tickets-heading__title">{ header }</div>
			{ description && <div className="tickets-heading__description">{ description }</div> }
		</div>
		<div className="tickets-heading__actions">{ actions }</div>
	</div>
);

const Card = ( { className, children, header, description, actions } ) => {
	return (
		<div className={ classNames( 'tribe-editor__card', className ) }>
			{ header && actions && getHeaderWithActions( header, description, actions ) }
			{ header && ! actions && getHeaderElement( header, description ) }
			{ children }
		</div>
	);
};

Card.propTypes = {
	actions: PropTypes.node,
	className: PropTypes.string,
	children: PropTypes.node,
	header: PropTypes.string,
	description: PropTypes.string,
};

export default Card;
