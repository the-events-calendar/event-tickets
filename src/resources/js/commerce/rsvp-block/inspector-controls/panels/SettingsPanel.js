/**
 * Settings panel for RSVP block inspector controls
 *
 * @since TBD
 */
import { RSVPSettingsFill } from '../slots';
import { applyFilters } from '@wordpress/hooks';
import { useState } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import CantGoControl from '../../components/cant-go-control';

/**
 * Settings panel content component
 *
 * @since TBD
 *
 * @param {Object}   props                 Component props.
 * @param {Object}   props.attributes      Block attributes.
 * @param {Function} props.setAttributes   Function to update attributes.
 * @param {boolean}  props.isLoading       Whether data is loading.
 * @param {Function} props.refetchRsvp     Function to refetch RSVP data.
 *
 * @return {JSX.Element} The settings panel content.
 */
function SettingsPanelContent( props ) {
	const { attributes, setAttributes, isLoading, refetchRsvp } = props;

	// Track if any toggle is updating
	const [ isAnyToggleUpdating, setIsAnyToggleUpdating ] = useState( false );

	const handleUpdateStart = () => {
		setIsAnyToggleUpdating( true );
	};

	const handleUpdateEnd = () => {
		setIsAnyToggleUpdating( false );
	};

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
		{
			attributes,
			setAttributes,
			isLoading,
			refetchRsvp,
			onUpdateStart: handleUpdateStart,
			onUpdateEnd: handleUpdateEnd
		}
	);

	return (
		<div style={ { position: 'relative' } }>
			{ isAnyToggleUpdating && (
				<div
					style={ {
						position: 'absolute',
						top: 0,
						left: 0,
						right: 0,
						bottom: 0,
						backgroundColor: 'rgba(255, 255, 255, 0.8)',
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'center',
						zIndex: 10,
					} }
				>
					<Spinner />
				</div>
			) }
			<CantGoControl
				attributes={ attributes }
				setAttributes={ setAttributes }
				isLoading={ isLoading }
				refetchRsvp={ refetchRsvp }
				onUpdateStart={ handleUpdateStart }
				onUpdateEnd={ handleUpdateEnd }
			/>
			{ additionalSettings }
		</div>
	);
}

/**
 * Settings panel component
 *
 * @since TBD
 *
 * @return {JSX.Element} The settings panel component.
 */
export function SettingsPanel() {
	return (
		<RSVPSettingsFill>
			{ ( props ) => <SettingsPanelContent { ...props } /> }
		</RSVPSettingsFill>
	);
}
