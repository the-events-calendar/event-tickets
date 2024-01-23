import { Card } from '@moderntribe/tickets/elements';
import React from 'react';
import TicketContainerHeaderTitle from '../../../../Ticket/app/editor/container-header/title/template';
import TicketContainerHeaderDescription from '../../../../Ticket/app/editor/container-header/description/template';
import TicketContainerHeaderPrice from '../../../../Ticket/app/editor/container-header/price/template';
import TicketContainerHeaderQuantity from '../../../../Ticket/app/editor/container-header/quantity/template';
import { Spinner } from '@wordpress/components';
import './style.pcss';

const Uneditable = ({ tickets, cardsByTicketType, cardClassName, loading }) => {
	if (loading) {
		return (
			<div className="tribe-editor__uneditable__loader">
				<Spinner />
			</div>
		);
	}

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
				key={ticketType}
				className={cardClassName + ' tribe-editor__card--uneditable'}
				header={cardsByTicketType[ticketType].title}
				description={cardsByTicketType[ticketType]?.description || null}
			>
				{ticketsByType[ticketType].map((ticket, index) => (
					<article
						className="tribe-editor__ticket"
						key={ticketType + '-' + index}
					>
						<div className="tribe-editor__container-panel tribe-editor__container-panel--ticket tribe-editor__ticket__container">
							<div className="tribe-editor__container-panel__header">
								<>
									<div className="tribe-editor__ticket__container-header-details">
										<TicketContainerHeaderTitle
											title={ticket.title}
											showAttendeeRegistrationIcons={
												false
											}
										/>
										<TicketContainerHeaderDescription
											description={ticket.description}
										/>
									</div>
									<TicketContainerHeaderPrice
										available={ticket.available}
										currencyDecimalPoint={
											ticket.currencyDecimalPoint
										}
										currencyNumberOfDecimals={
											ticket.currencyNumberOfDecimals
										}
										currencyPosition={
											ticket.currencyPosition
										}
										currencySymbol={ticket.currencySymbol}
										currencyThousandsSep={
											ticket.currencyThousandsSep
										}
										isUnlimited={
											ticket.capacityType === 'unlimited'
										}
										price={ticket.price}
									/>
									<TicketContainerHeaderQuantity
										isShared={ticket.isShared}
										isUnlimited={
											ticket.capacityType === 'unlimited'
										}
										sold={ticket.sold}
										capacity={ticket.capacity}
										sharedSold={ticketType.sharedSold}
										sharedCapacity={
											ticketType.sharedCapacity
										}
									/>
								</>
							</div>
						</div>
					</article>
				))}
			</Card>
		);
	});
};

export default Uneditable;
