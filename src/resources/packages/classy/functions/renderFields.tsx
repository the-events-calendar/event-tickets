import React, { Fragment, useEffect } from 'react';
import { Fill } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { Tickets, VirtualLocationViewingPermissions, VirtualLocationAdditionalSettings } from '../fields';
import { getSettings } from '../localizedData';
import { StoreDispatch as TECStoreDispatch, StoreSelect as TECStoreSelect } from '@tec/events/classy/types/Store';
import { CoreEditorSelect } from '@tec/common/classy/types/Store';

/*
 * Hard-code the TEC store name to avoid trying to load it from the window object at module-load time,
 * when TEC might have not been loaded yet.
 */
const TEC_STORE_NAME = 'tec/classy/events';

/**
 * The post types that should render the ticket fields.
 *
 * @since TBD
 */
const { ticketPostTypes } = getSettings();

/**
 * Renders the ticket fields in the Classy editor.
 *
 * @since TBD
 *
 * @return {ComponentType} The component with ticket fields.
 * @param fields
 */
export default function renderFields( fields: React.ReactNode | null ): React.ReactNode {
	const { postType, areTicketsSupported } = useSelect( ( select ) => {
		const { getEditedPostAttribute }: CoreEditorSelect = select( 'core/editor' );
		const { areTicketsSupported }: TECStoreSelect = select( TEC_STORE_NAME );

		return {
			postType: getEditedPostAttribute( 'type' ),
			areTicketsSupported: areTicketsSupported(),
		};
	}, [] );

	// Ensure we are only adding fields to the correct post type(s).
	if ( ! ticketPostTypes.includes( postType ) ) {
		console.log( 'not rendering ticket fields for post type:', postType );
		return fields;
	}

	const { setTicketsSupported }: TECStoreDispatch = useDispatch( TEC_STORE_NAME );

	// Ensure tickets are marked as supported in the store.
	useEffect( () => {
		if ( ! areTicketsSupported ) {
			setTicketsSupported( true );
		}
	}, [ areTicketsSupported, setTicketsSupported ] );

	return (
		<Fragment>
			{ /* Render the fields passed to this function first. */ }
			{ fields }

			{ /* Portal-render the fields into the Classy form. */ }
			<Fill name="tec.classy.fields.tickets">
				<Tickets />
			</Fill>

			{ /* Portal-render the fields into the Virtual Event settings form. */ }
			<Fill name="tec.classy.virtual-location.settings.viewing-permissions.after">
				<VirtualLocationViewingPermissions />
			</Fill>

			{ /* Portal-render the fields into the Virtual Event settings form. */ }
			<Fill name="tec.classy.virtual-location.settings.after">
				<VirtualLocationAdditionalSettings />
			</Fill>
		</Fragment>
	);
}
