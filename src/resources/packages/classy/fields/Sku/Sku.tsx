import * as React from 'react';
import { LabeledInput } from '@tec/common/classy/components';
import { __experimentalInputControl as InputControl } from '@wordpress/components';
import { __, _x } from '@wordpress/i18n';
import { TicketComponentProps } from '../../types/TicketComponentProps';

const defaultLabel = __( 'Ticket SKU', 'event-tickets' );

/**
 * Renders the ticket sku field in the Classy editor.
 *
 * @since TBD
 *
 * @param {TicketComponentProps} props
 * @return {JSX.Element} The rendered ticket sku field.
 */
export default function Sku( props: TicketComponentProps ): JSX.Element {
	const { label = defaultLabel, onChange, value } = props;

	return (
		<LabeledInput label={ label }>
			<InputControl
				className="classy-field__control classy-field__control--input"
				label={ label }
				hideLabelFromVision={ true }
				value={ value }
				onChange={ onChange }
			/>
			<div className="classy-field__input-note">
				{ _x(
					'A unique identifying code for each ticket you are selling.',
					'Ticket SKU input note',
					'event-tickets'
				) }
			</div>
		</LabeledInput>
	);
}
