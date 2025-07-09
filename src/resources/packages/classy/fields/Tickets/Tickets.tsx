import * as React from 'react';
import { Fragment, useCallback, useState } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectFunction } from '@wordpress/data/build-types/types';
import { CenteredSpinner } from '@tec/common/classy/components';
import { _x } from '@wordpress/i18n';
import {
	AddTicket,
	TicketUpsertModal,
} from '../../components';
import { Ticket as TicketData } from '../../types/Ticket';
import { STORE_NAME } from '../../constants';

const defaultTicket: Partial<TicketData> = {
	title: '',
	description: '',
	cost: '',
	salePriceData: {
		enabled: false,
		salePrice: '',
		startDate: null,
		endDate: null,
	}
};

/**
 * Tickets component to display and manage tickets for an event.
 *
 * @since TBD
 *
 * @return {JSX.Element} The rendered component.
 */
export default function Tickets(): JSX.Element {
	const { tickets } = useSelect( ( select: SelectFunction ) => {
		const {
			getTickets,
		}: {
			getTickets: ( eventId: number ) => TicketData[];
		} = select( STORE_NAME );

		const { getCurrentPostId }: {
			getCurrentPostId: () => number | null
		} = select( 'core/editor' );

		return {
			tickets: getTickets( getCurrentPostId() ) || null,
		};
	}, [] )

	// If the tickets are not yet loaded, show a spinner.
	if ( ! tickets ) {
		return <CenteredSpinner />;
	}

	const { setTickets } = useDispatch( STORE_NAME );

	const [ hasTickets, setHasTickets ] = useState( tickets.length > 0 );
	const [ isUpserting, setIsUpserting ] = useState( false );

	const onTicketAddedClicked = useCallback( () => {
		setIsUpserting( true );
	}, [ isUpserting ] );

	const onTicketUpsertSaved = useCallback( ( ticket: TicketData ) => {
		setIsUpserting( false );

		// If the ticket is new, add it to the list of tickets.
		if ( ! hasTickets ) {
			setHasTickets( true );
		}

		tickets.push( ticket );
		setTickets( tickets );
	}, [
		hasTickets,
		tickets,
	] );

	return (
		<div className="classy-field classy-field--tickets">
			{ isUpserting && (
				<TicketUpsertModal
					isUpdate={ ! hasTickets }
					onCancel={ () => setIsUpserting( false ) }
					onClose={ () => setIsUpserting( false ) }
					onSave={ onTicketUpsertSaved }
					values={ defaultTicket }
				/>
			) }

			{ hasTickets && (
				<Fragment>
					{ allTickets.map( ( ticket: TicketData ) => (
						<div>
							<code>{ JSON.stringify( ticket ) }</code>
						</div>
					) ) }
				</Fragment>
			) }

			{ ! hasTickets && (
				<AddTicket
					buttonText={ _x( 'Add Tickets', 'Button text to add a new ticket', 'event-tickets' ) }
					onClick={ onTicketAddedClicked }
				/>
			) }
		</div>
	);
}
