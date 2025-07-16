import * as React from 'react';
import { Fragment } from 'react';
import { Ticket } from '../../types/Ticket';
import { TicketComponentProps } from '../../types/TicketComponentProps';
import { Button } from '@wordpress/components';

type TicketTableProps = {
	tickets: Ticket[];
	onEditTicket: ( ticket: Ticket ) => void;
} & Omit<TicketComponentProps, 'value'>;

export default function TicketTable( props: TicketTableProps ): JSX.Element {
	const {
		tickets,
		onEditTicket,
	} = props;

	return (
		<Fragment>
			{ tickets.map( ( ticket: Ticket ) => (
				<div key={ ticket.id }>
					<pre><code style={ { display: 'block' } }>{ JSON.stringify( ticket, null, '\t' ) }</code></pre>
					<Button
						onClick={ () => onEditTicket( ticket ) }
						variant="link"
					>Edit Ticket</Button>
				</div>
			) ) }
		</Fragment>
	);
}
