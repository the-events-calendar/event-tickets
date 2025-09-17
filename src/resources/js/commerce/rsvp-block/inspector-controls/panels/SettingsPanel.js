/**
 * Settings panel for RSVP block inspector controls
 *
 * @since TBD
 */
import { RSVPSettingsFill } from '../slots';
import { applyFilters } from '@wordpress/hooks';
import CantGoControl from '../../components/cant-go-control';

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
			{ ( props ) => {
				const { attributes, setAttributes, isLoading, refetchRsvp } = props;

				/**
				 * Filters the additional settings for the RSVP settings panel.
				 *
				 * @since TBD
				 *
				 * @param {JSX|null} additionalSettings Additional settings JSX or null.
				 * @param {Object}   props              Settings panel props.
				 *
				 * @return {JSX|null} Additional settings to render.
				 */
				const additionalSettings = applyFilters(
					'tec.tickets.commerce.rsvp.settingsPanel',
					null,
					{ attributes, setAttributes, isLoading, refetchRsvp }
				);

				return (
					<>
						<CantGoControl
							attributes={ attributes }
							setAttributes={ setAttributes }
							isLoading={ isLoading }
							refetchRsvp={ refetchRsvp }
						/>
						{ additionalSettings }
					</>
				);
			} }
		</RSVPSettingsFill>
	);
}
