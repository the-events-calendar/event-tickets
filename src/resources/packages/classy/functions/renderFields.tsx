import React, { Fragment } from 'react';
import { Fill } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { Tickets } from '../fields';
import { CoreEditorSelect } from '../types/Store';

// Hard-code the post type for now.
const POST_TYPES: string[] = [ 'tec_tc_ticket', 'tribe_events' ];

/**
 * Renders the ticket fields in the Classy editor.
 *
 * @since TBD
 *
 * @return {ComponentType} The component with ticket fields.
 * @param fields
 */
export default function renderFields( fields: React.ReactNode | null ): React.ReactNode {
	const { postType } = useSelect( ( select ) => {
		const { getEditedPostAttribute }: CoreEditorSelect = select( 'core/editor' );

		return {
			postType: getEditedPostAttribute( 'type' ),
		};
	}, [] );

	// Ensure we are only adding fields to the correct post type(s).
	if ( ! POST_TYPES.includes( postType ) ) {
		console.log( 'not rendering ticket fields for post type:', postType );
		return fields;
	}

	return (
		<Fragment>
			{ /* Render the fields passed to this function first. */ }
			{ fields }

			{ /* Portal-render the fields into the Classy form. */ }
			<Fill name="tec.classy.fields.tickets">

				<Tickets />

			</Fill>
		</Fragment>
	);
};
