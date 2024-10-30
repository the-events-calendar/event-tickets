/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';

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

const TicketContainerContent = ({ clientId, hasTicketsPlus, hasIacVars }) => (
	<Fragment>
		<Title clientId={clientId} />
		<Description clientId={clientId} />
		<Price clientId={clientId} />
		<Type clientId={clientId} />
		<Capacity clientId={clientId} />
		<Duration clientId={clientId} />
		<AdvancedOptions clientId={clientId} />
		{hasTicketsPlus && hasIacVars && (
			<AttendeeCollection clientId={clientId} />
		)}
		{hasTicketsPlus && <AttendeesRegistration clientId={clientId} />}
	</Fragment>
);

TicketContainerContent.propTypes = {
	clientId: PropTypes.string.isRequired,
	hasTicketsPlus: PropTypes.bool,
	hasIacVars: PropTypes.bool,
};

export default TicketContainerContent;
