import * as React from 'react';
import { _x } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import {
	METADATA_EVENTS_VIRTUAL_RSVP_EMAIL_LINK,
	METADATA_EVENTS_VIRTUAL_TICKET_EMAIL_LINK,
} from '../../constants.tsx';
import useMetaFiltering from './useMetaFiltering.ts';
import { CoreEditorSelect } from '../../types/Store';

export default function AdditionalSettings(): JSX.Element {
	const meta: {
		includeVideoLinkInRsvpEmails: boolean;
		includeVideoLinkInTicketEmails: boolean;
	} = useSelect( ( select ) => {
		const store: CoreEditorSelect = select( 'core/editor' );

		const meta = store.getEditedPostAttribute( 'meta' );

		return {
			includeVideoLinkInRsvpEmails: ( meta[ METADATA_EVENTS_VIRTUAL_RSVP_EMAIL_LINK ] ?? '' ) === 'yes',
			includeVideoLinkInTicketEmails: ( meta[ METADATA_EVENTS_VIRTUAL_TICKET_EMAIL_LINK ] ?? '' ) === 'yes',
		};
	}, [] );

	const [ includeVideoLinkInRsvpEmails, setIncludeVideoLinkInRsvpEmails ] = useState< boolean >(
		meta.includeVideoLinkInRsvpEmails
	);
	const [ includeVideoLinkInTicketEmails, setIncludeVideoLinkInTicketEmails ] = useState< boolean >(
		meta.includeVideoLinkInTicketEmails
	);

	useMetaFiltering(
		'additional-settings',
		( meta: Object ): Object => {
			meta[ METADATA_EVENTS_VIRTUAL_RSVP_EMAIL_LINK ] = includeVideoLinkInRsvpEmails ? 'yes' : '';
			meta[ METADATA_EVENTS_VIRTUAL_TICKET_EMAIL_LINK ] = includeVideoLinkInTicketEmails ? 'yes' : '';
			return meta;
		},
		( meta: Object ): Object => {
			meta[ METADATA_EVENTS_VIRTUAL_RSVP_EMAIL_LINK ] = null;
			meta[ METADATA_EVENTS_VIRTUAL_TICKET_EMAIL_LINK ] = null;

			return meta;
		},
		[ includeVideoLinkInRsvpEmails, includeVideoLinkInTicketEmails ]
	);

	return (
		<section className="classy-modal__section">
			<h5 className="classy-modal__section-title">
				{ _x( 'Include video link', 'Virtual location settings section title', 'event-tickets' ) }
			</h5>

			<ToggleControl
				className="classy-modal__section-text"
				__nextHasNoMarginBottom
				label={ _x( 'In RSVP emails', 'Setting label', 'event-tickets' ) }
				onChange={ ( newValue: boolean ): void => setIncludeVideoLinkInRsvpEmails( newValue ) }
				checked={ includeVideoLinkInRsvpEmails }
			/>

			<ToggleControl
				className="classy-modal__section-text"
				__nextHasNoMarginBottom
				label={ _x( 'In Ticket emails', 'Setting label', 'event-tickets' ) }
				onChange={ ( newValue: boolean ): void => setIncludeVideoLinkInTicketEmails( newValue ) }
				checked={ includeVideoLinkInTicketEmails }
			/>
		</section>
	);
}
