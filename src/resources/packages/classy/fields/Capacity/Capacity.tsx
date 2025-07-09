import * as React from 'react';
import { RadioControl } from '@wordpress/components';
import { _x } from '@wordpress/i18n';
import { TicketComponentProps } from '../../types/TicketComponentProps';
import { Capacity as CapacityType } from '../../types/Ticket';

type CapacityProps = {
	value: CapacityType;
} & TicketComponentProps;

const defaultValue: CapacityType = 'general-admission';

const capacityOptions: { label: string, value: CapacityType }[] = [
	{
		label: _x( 'General Admission', 'Label for general admission capacity type', 'event-tickets' ),
		value: 'general-admission',
	},
	{
		label: _x( 'Assigned Seating', 'Label for reserved seating capacity type', 'event-tickets' ),
		value: 'assigned-seating',
	},
];

/**
 * Renders the capacity type field in the Classy editor.
 *
 * @param {CapacityProps} props
 * @return {JSX.Element} The rendered capacity type field.
 */
export default function Capacity( props: CapacityProps ): JSX.Element {
	const { value, onChange } = props;

	return (
		<RadioControl
			className="classy-field__control classy-field__control--radio"
			onChange={ onChange }
			selected={ value || defaultValue }
			label={ _x( 'Select a capacity type', 'Label for the capacity type selection', 'event-tickets' ) }
			hideLabelFromVision={ true }
			options={ capacityOptions }
		/>
	);
}
