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
 * @since 5.18.0
 *
 * @param {string} clientId       The client ID.
 * @param {bool}   hasTicketsPlus Whether the site has Tickets Plus.
 * @param {bool}   hasIacVars     Whether the site has IAC vars.
 * @return {*}
 */
const getTicketContainerItems = ( clientId, hasTicketsPlus, hasIacVars ) => {
	let items = [
		{
			item: <Title clientId={ clientId }/>,
			key: 'title',
		},
		{
			item: <Description clientId={ clientId }/>,
			key: 'description',
		},
		{
			item: <Price clientId={ clientId }/>,
			key: 'price',
		},
		{
			item: <Type clientId={ clientId }/>,
			key: 'type',
		},
		{
			item: <Capacity clientId={ clientId }/>,
			key: 'capacity',
		},
		{
			item: <Duration clientId={ clientId }/>,
			key: 'duration',
		},
		{
			item: <AdvancedOptions clientId={ clientId }/>,
			key: 'advancedOptions',
		},
	];

	if ( hasTicketsPlus && hasIacVars ) {
		items.push( {
			item: <AttendeeCollection clientId={ clientId }/>,
			key: 'attendeeCollection',
		} );
	}

	if ( hasTicketsPlus ) {
		items.push( {
			item: <AttendeesRegistration clientId={ clientId }/>,
			key: 'attendeesRegistration',
		} );
	}

	/**
	 * Filters the ticket container items.
	 *
	 * @since 5.18.0
	 *
	 * @param {object[]} items    The ticket container items.
	 * @param {string}   clientId The client ID.
	 */
	items = applyFilters(
		'tec.ticket.container.items',
		items,
		clientId,
	);

	return items;
}

const TicketContainerContent = ( { clientId, hasTicketsPlus, hasIacVars } ) => {
	return (
		<Fragment>
			{getTicketContainerItems(clientId, hasTicketsPlus, hasIacVars).map((item) => (
				<Fragment key={item.key}>
					{item.item}
				</Fragment>
			))}
		</Fragment>
	);};

TicketContainerContent.propTypes = {
	clientId: PropTypes.string.isRequired,
	hasTicketsPlus: PropTypes.bool,
	hasIacVars: PropTypes.bool,
};

export default TicketContainerContent;
