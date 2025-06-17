import * as React from 'react';
import { LabeledInput } from '@tec/common/classy/components';
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
		<LabeledInput label={ label || defaultLabel }>
			<InputControl
				className="classy-field__control classy-field__control--input"
				label={ label || defaultLabel }
				hideLabelFromVision={ true }
				value={ value }
				onChange={ onChange }
				required={ true }
			/>
		</LabeledInput>
	);
}
