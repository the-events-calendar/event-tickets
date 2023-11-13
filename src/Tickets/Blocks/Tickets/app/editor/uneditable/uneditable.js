import { Card } from '@moderntribe/tickets/elements';
import React from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { withStore } from '@moderntribe/common/hoc';

const Uneditable = ({ tickets, cardsByTicketType, cardClassName }) => {
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
				header={cardsByTicketType[ticketType].title}
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
			description: 'This is a description for Series Pass One',
			capacityType: 'unlimited',
			price: '$23.00',
			capacity: 100,
			available: 89,
		},
		{
			type: 'series_pass',
			title: 'Series Pass Two',
			description: 'This is a description for Series Pass Two',
			capacityType: 'global',
			price: '$89.00',
			capacity: 200,
			available: 12,
		},
	],
	cardsByTicketType: {
		series_pass: {
			title: 'Series Passes',
			noticeHtml: 'This event is part of a Series ...', // This will be sanitized HTML, to be dang. set.
			link: 'https://example.com',
		},
	},
};

const mapStateToProps = (state, ownProps) => ({
	tickets: mocks.tickets,
	cardsByTicketType: mocks.cardsByTicketType,
});

export default compose(withStore(), connect(mapStateToProps))(Uneditable);
