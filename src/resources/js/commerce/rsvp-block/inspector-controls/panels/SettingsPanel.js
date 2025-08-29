/**
 * Settings panel for RSVP block inspector controls
 *
 * @since TBD
 */
import { RSVPSettingsFill } from '../slots';
import { CapacityField } from '../fields/CapacityField';

/**
 * Settings panel component
 *
 * @since TBD
 *
 * @param {Object}   props                 Component props.
 * @param {Object}   props.attributes      Block attributes.
 * @param {Function} props.setAttributes   Function to update attributes.
 * @param {boolean}  props.isLoading       Whether data is loading.
 * @param {Object}   props.error           Error object if any.
 * @param {Object}   props.rsvpData        Current RSVP data.
 * @param {Object}   props.updateMutation  React Query mutation object.
 *
 * @return {JSX.Element} The settings panel component.
 */
export function SettingsPanel() {
	return (
		<RSVPSettingsFill>
			{ ( { attributes, setAttributes, isLoading } ) => {
				const { limit } = attributes || {};

				const handleCapacityChange = ( newLimit ) => {
					setAttributes( { limit: newLimit } );
				};

				return (
					<>
						<CapacityField
							value={ limit }
							onChange={ handleCapacityChange }
							disabled={ isLoading }
						/>
						{ /* Date fields will be added here later */ }
						{ /* Attendee info collection field will be added here later */ }
					</>
				);
			} }
		</RSVPSettingsFill>
	);
}