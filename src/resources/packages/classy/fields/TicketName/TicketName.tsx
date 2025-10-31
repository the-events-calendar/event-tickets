import * as React from 'react';
import { __experimentalInputControl as InputControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { TicketComponentProps } from '../../types/TicketComponentProps';

/**
 * Renders the ticket name field in the Classy editor.
 *
 * @since TBD
 *
 * @param {TicketComponentProps} props
 * @return {JSX.Element} The rendered ticket name field.
 */
export default function TicketName( props: TicketComponentProps ): JSX.Element {
	const { label, onChange, value } = props;
	const defaultLabel = __( 'Ticket name', 'event-tickets' );

	return (
		<InputControl
			className="classy-field__control classy-field__control--input"
			label={ label || defaultLabel }
			value={ value }
			onChange={ onChange }
			required={ true }
		/>
	);
}
