import * as React from 'react';
import { Fragment, useEffect, useState } from 'react';
import { _x } from '@wordpress/i18n';
import {
	AddTicket
} from '../../components';

type TicketsProps = {
	tickets: []
};

export default function Tickets( props: TicketsProps ): JSX.Element {

	const { tickets } = props;

	const [ hasTickets, setHasTickets ] = React.useState( tickets.length > 0 );

	return (
		<Fragment>
			{ ! hasTickets && (
				<AddTicket
					buttonText={ _x( 'Add Tickets', 'Button text to add a new ticket', 'event-tickets' ) }
					onClick={ () => console.log( 'Add ticket clicked' ) }
				/>
			) }
		</Fragment>
	);
}
