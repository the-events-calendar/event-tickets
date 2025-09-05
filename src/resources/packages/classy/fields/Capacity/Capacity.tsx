import * as React from 'react';
import { useState } from 'react';
import { LabeledInput } from '@tec/common/classy/components';
import { __experimentalInputControl as InputControl, RadioControl, Slot, ToggleControl } from '@wordpress/components';
import { _x } from '@wordpress/i18n';
import { CapacitySettings, Seating } from '../../types/Ticket';
import { TicketComponentProps } from '../../types/TicketComponentProps';

type CapacityProps = {
	value: CapacitySettings;
	onChange: ( value: CapacitySettings ) => void;
} & TicketComponentProps;

const defaultValue: CapacitySettings = {
	enteredCapacity: '',
	isShared: false,
	globalStockMode: 'own',
};

const capacityOptions: { label: string; value: Seating }[] = [
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
 * Renders the capacity fields in the Classy editor.
 *
 * @param {CapacityProps} props
 * @return {JSX.Element} The rendered capacity type field.
 */
export default function Capacity( props: CapacityProps ): JSX.Element {
	const { value, onChange } = props;

	const [ currentValue, setCurrentValue ] = useState< CapacitySettings >( {
		...defaultValue,
		...value,
	} );

	const onValueChange = ( key: keyof CapacitySettings, newValue: any ) => {
		const updatedValue = {
			...currentValue,
			[ key ]: newValue,
		};
		setCurrentValue( updatedValue );
		onChange( updatedValue );
	};

	let capacityValue: string;
	if ( currentValue.enteredCapacity === '' ) {
		capacityValue = '';
	} else if ( Number( currentValue.enteredCapacity ) > 0 ) {
		capacityValue = String( currentValue.enteredCapacity );
	} else {
		capacityValue = '';
	}

	return (
		<div className="classy-field__capacity">
			{
				/**
				 * Slot for additional content before the capacity fields.
				 *
				 * This slot allows for customization of the capacity section, such as adding
				 * the seating chart or other related controls.
				 *
				 * This slot provides some properties to the fill component:
				 * - `value`: The current capacity settings.
				 * - `onChange`: A function to update the capacity settings.
				 *
				 * Example:
				 * ```
				 * addFilter(
				 * 	'tec.classy.render',
				 * 	'tec.classy.my-plugin',
				 * 	( fields: React.ReactNode | null ) => (
				 * 		<Fragment>
				 * 			{ fields }
				 * 			<Fill name="tec.classy.fields.tickets.capacity.before">
				 * 				{
				 * 					( { value, onChange } ) => (
				 * 						<Button
				 * 							value={ value }
				 * 							onClick={ ( newValue ) => onChange( newValue ) }
				 * 						>
				 * 							CLICK ME FROM MY PLUGIN
				 * 						</Button>
				 * 					)
				 * 				}
				 * 			</Fill>
				 * 		</Fragment>
				 * 	)
				 * );
				 * ```
				 *
				 * @since TBD
				 */
				<Slot
					name="tec.classy.fields.tickets.capacity.before"
					fillProps={ {
						value: currentValue,
						onChange: onValueChange,
					} }
				/>
			}

			<LabeledInput label={ _x( 'Ticket Capacity', 'Label for the ticket capacity field', 'event-tickets' ) }>
				<InputControl
					__next40pxDefaultSize={ true }
					className="classy-field__control classy-field__control--input classy-field__control--input-narrow"
					label={ _x( 'Ticket Capacity', 'Label for the ticket capacity field', 'event-tickets' ) }
					hideLabelFromVision={ true }
					value={ capacityValue }
					onChange={ ( value: string ) => {
						const capacityValue = value ? Math.abs( parseInt( value, 10 ) ) : '';
						return onValueChange( 'enteredCapacity', capacityValue );
					} }
					size="small"
					placeholder={ _x( 'unlimited', 'Placeholder for unlimited capacity', 'event-tickets' ) }
				/>
				<div className="classy-field__input-note">
					{ _x( 'Leave blank for unlimited', 'Ticket capacity input note', 'event-tickets' ) }
				</div>
			</LabeledInput>

			{
				/**
				 * Slot for additional content after the capacity fields.
				 *
				 * This slot allows for customization of the capacity section, such as adding
				 * the seating chart or other related controls.
				 *
				 * This slot provides some properties to the fill component:
				 * - `value`: The current capacity settings.
				 * - `onChange`: A function to update the capacity settings.
				 *
				 * Example:
				 * ```
				 * addFilter(
				 * 	'tec.classy.render',
				 * 	'tec.classy.my-plugin',
				 * 	( fields: React.ReactNode | null ) => (
				 * 		<Fragment>
				 * 			{ fields }
				 * 			<Fill name="tec.classy.fields.tickets.capacity.after">
				 * 				{
				 * 					( { value, onChange } ) => (
				 * 						<Button
				 * 							value={ value }
				 * 							onClick={ ( newValue ) => onChange( newValue ) }
				 * 						>
				 * 							CLICK ME FROM MY PLUGIN
				 * 						</Button>
				 * 					)
				 * 				}
				 * 			</Fill>
				 * 		</Fragment>
				 * 	)
				 * );
				 * ```
				 *
				 * @since TBD
				 */
				<Slot
					name="tec.classy.fields.tickets.capacity.after"
					fillProps={ {
						value: currentValue,
						onChange: onValueChange,
					} }
				/>
			}
		</div>
	);
}
