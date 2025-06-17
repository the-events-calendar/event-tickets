import * as React from 'react';
import { LabeledInput } from '@tec/common/classy/components';
import { __experimentalInputControl as InputControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { TicketComponentProps } from '../../types/TicketComponentProps';


export default function TicketName( props: TicketComponentProps ) {
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
			/>
		</LabeledInput>
	);
}
