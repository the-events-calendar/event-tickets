import { Card } from '@moderntribe/tickets/elements';
import React from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { withStore } from '@moderntribe/common/hoc';

const Uneditable = ({ tickets, cardTitlesByType, cardClassName }) => {
	const ticketTypes = tickets.reduce((acc, ticket) => {
		return acc.indexOf(ticket.type) === -1 ? [...acc, ticket.type] : acc;
	}, []);
	const ticketsByType = tickets.reduce((acc, ticket) => {
		const { type } = ticket;
		if (!acc[type]) {
			acc[type] = [];
		}
		acc[type].push(ticket);
		return acc;
	}, {});

	return ticketTypes.map((ticketType) => {
		return (
			<Card
				className={cardClassName}
				header={cardTitlesByType[ticketType]}
			>
				{ticketsByType[ticketType].map((ticket, index) => (
					<div key={index + ticketType}>{ticket.title}</div>
				))}
			</Card>
		);
	});
};

const mocks = {
	tickets: [
		{
			type: 'series_pass',
			title: 'Series Pass One',
		},
		{
			type: 'series_pass',
			title: 'Series Pass Two',
		},
		{
			type: 'series_pass',
			title: 'Series Pass Three',
		},
		{
			type: 'series_ticket',
			title: 'Series Ticket One',
		},
		{
			type: 'series_ticket',
			title: 'Series Ticket Two',
		},
	],
	cardTitlesByType: {
		series_pass: 'Series Passes',
		series_ticket: 'Series Tickets',
	},
};

const mapStateToProps = (state, ownProps) => ({
	tickets: mocks.tickets,
	cardTitlesByType: mocks.cardTitlesByType,
});

export default compose(withStore(), connect(mapStateToProps))(Uneditable);
