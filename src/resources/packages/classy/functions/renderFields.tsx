import React, { Fragment, useState } from 'react';
import { Fill } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	Tickets,
} from '../fields';

type Ticket = {
	name: string;
	description?: string;
	price?: string;
}

/**
 * Renders the ticket fields in the Classy editor.
 *
 * @since TBD
 *
 * @return {ComponentType} The component with ticket fields.
 * @param fields
 */
export default function renderFields( fields: React.ReactNode | null ): React.ReactNode {

	// todo: Add post type check?

	const meta = useSelect( ( select ) => {
		const { getEditedPostAttribute }: {
			getEditedPostAttribute: ( attribute: string ) => any;
		} = select( 'core/editor' );

		return getEditedPostAttribute( 'meta' ) || null;
	}, [] );

	// todo: Use the correct format/meta for ticket data.
	const ticketMeta: Ticket = meta?.ticket || { name: '' };

	const { editPost } = useDispatch( 'core/editor' );


	// todo: the ticket fields need to be rendered per ticket, in a modal.
	// todo: Display of the tickets has a different format when not editing.
	return (
		<Fragment>
			{ /* Render the fields passed to this function first. */ }
			{ fields }

			{ /* Portal-render the fields into the Classy form. */ }
			<Fill name="tec.classy.fields.tickets">

				<Tickets
					tickets={ [] }
				/>

			</Fill>

		</Fragment>
	);
};
