import * as React from 'react';
import { __ } from '@wordpress/i18n';
import { TextareaControl } from '@wordpress/components';
import { TicketComponentProps } from '../../types/TicketComponentProps';

/**
 * Renders the ticket description field in the Classy editor.
 *
 * @param {TicketComponentProps} props
 * @return {JSX.Element} The rendered ticket description field.
 */
export default function TicketDescription( props: TicketComponentProps ): JSX.Element {
	const { label, onChange, value } = props;
	const defaultLabel = __( 'Description', 'event-tickets' );

	return (
		<TextareaControl
			className="classy-field__control classy-field__control--textarea"
			label={ label || defaultLabel }
			value={ value }
			onChange={ onChange }
			required={ false }
		/>
	);
}
