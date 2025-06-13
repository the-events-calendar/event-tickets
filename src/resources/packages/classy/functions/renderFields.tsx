import React, { Fragment } from 'react';
import { Fill } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { STORE_NAME } from '../store/constants';
import { StoreSelectors } from '../types/store';

/**
 * Renders the ticket fields in the Classy editor.
 *
 * @since TBD
 *
 * @return {ComponentType} The component with ticket fields.
 * @param fields
 */
export default function renderFields( fields: React.ReactNode | null ): React.ReactNode {

	// todo: Add post type check if needed.

	return (
		<Fragment>
			{ /* Render the fields passed to this function first. */ }
			{ fields }

			{ /* Portal-render the fields into the Classy form. */ }
			<Fill name="tec.classy.fields.before">
				<p>Hello from Event Tickets!</p>
			</Fill>

			<Fill name="tec.classy.fields.tickets">
				<p>Hello from Ticket Fields!</p>
			</Fill>

		</Fragment>
	);
};
