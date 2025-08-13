import * as React from 'react';
import { _x } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

type AddTicketProps = {
	buttonText?: string;
	onClick: () => void;
};

const defaultButtonText = _x( 'Add Ticket', 'Button text to add a new ticket', 'event-tickets' );

export default function AddTicket( props: AddTicketProps ): JSX.Element {
	const { buttonText, onClick } = props;

	return (
		<Button className="classy-button" onClick={ onClick } variant="primary">
			{ buttonText || defaultButtonText }
		</Button>
	);
}
