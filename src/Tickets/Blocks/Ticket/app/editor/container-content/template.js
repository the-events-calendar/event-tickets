/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import Capacity from './capacity/container';
import Duration from './duration/container';
import AdvancedOptions from './advanced-options/container';
import AttendeeCollection from './attendee-collection/container';
import AttendeesRegistration from './attendees-registration/container';
import Description from './description/container';
import Price from './price/container';
import Title from './title/container';
import Type from './type/container';
import './style.pcss';

/**
 * Get the ticket container items.
 *
 * @since TBD
 *
 * @param {string} clientId       The client ID.
 * @param {bool}   hasTicketsPlus Whether the site has Tickets Plus.
 * @param {bool}   hasIacVars     Whether the site has IAC vars.
 * @return {*}
 */
const getTicketContainerItems = ( clientId, hasTicketsPlus, hasIacVars ) => {
	const items = [
		<Title clientId={ clientId }/>,
		<Description clientId={ clientId }/>,
		<Price clientId={ clientId }/>,
		<Type clientId={ clientId }/>,
		<Capacity clientId={ clientId }/>,
		<Duration clientId={ clientId }/>,
		<AdvancedOptions clientId={ clientId }/>,
	];

	if ( hasTicketsPlus && hasIacVars ) {
		items.push( <AttendeeCollection clientId={ clientId }/> );
	}

	if ( hasTicketsPlus ) {
		items.push( <AttendeesRegistration clientId={ clientId }/> );
	}

	/**
	 * Filters the ticket container items.
	 *
	 * @since TBD
	 *
	 * @param {Array}  items    The ticket container items.
	 * @param {string} clientId The client ID.
	 */
	return Array.from( applyFilters(
		'tec.ticket.container.items',
		items,
		clientId,
	) );
}

const TicketContainerContent = ( { clientId, hasTicketsPlus, hasIacVars } ) => (
	<Fragment>
		{ getTicketContainerItems( clientId, hasTicketsPlus, hasIacVars ).map( ( item ) => ( item ) ) }
	</Fragment>
);

TicketContainerContent.propTypes = {
	clientId: PropTypes.string.isRequired,
	hasTicketsPlus: PropTypes.bool,
	hasIacVars: PropTypes.bool,
};

export default TicketContainerContent;
