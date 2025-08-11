import * as React from 'react';
import { _x } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { Settings } from '../../types/VirtualLocation';
import { useSelect } from '@wordpress/data';
import {
	METADATA_EVENTS_VIRTUAL_RSVP_EMAIL_LINK,
	METADATA_EVENTS_VIRTUAL_TICKET_EMAIL_LINK,
} from '../../constants.tsx';

const includeVideoLinkOptions: {
	label: string;
	value: 'rsvp' | 'ticket';
}[] = [
	{
		label: _x( 'In RSVP emails', 'Include videe link option label', 'event-tickets' ),
		value: 'rsvp',
	},
	{
		label: _x( 'In Ticket emails', 'Show when option label', 'tribe-events-calendar-pro' ),
		value: 'ticket',
	},
];

const defaultSettings: Settings = {
	includeVideoLinkInRsvpEmails: true,
	includeVideoLinkInTicketEmails: true,
};

export default function AdditionalSettings(): JSX.Element {
	const postSettings: Settings = useSelect( ( select ) => {
		const store: {
			getEditedPostAttribute: ( key: string ) => any;
		} = select( 'core/editor' );

		const meta = store.getEditedPostAttribute( 'meta' );
		const settingsFromMeta = { ...defaultSettings };

		[ METADATA_EVENTS_VIRTUAL_RSVP_EMAIL_LINK, METADATA_EVENTS_VIRTUAL_TICKET_EMAIL_LINK ].map(
			( metaKey: string ) => {
				settingsFromMeta[ metaKey ] = meta.hasOwnProperty( metaKey )
					? meta[ metaKey ] === 'yes'
					: defaultSettings[ metaKey ];
			}
		);

		return settingsFromMeta;
	}, [] );

	const [ settings, setSettings ] = useState< Settings >( postSettings );

	return (
		<section className="classy-modal__section">
			<h5 className="classy-modal__section-title">
				{ _x( 'Include video link', 'Virtual location settings section title', 'event-tickets' ) }
			</h5>

			<ToggleControl
				className="classy-modal__section-text"
				__nextHasNoMarginBottom
				label={ _x( 'In RSVP emails', 'Setting label', 'event-tickets' ) }
				onChange={ ( newValue: boolean ): void =>
					setSettings( {
						...settings,
						includeVideoLinkInRsvpEmails: newValue,
					} )
				}
				checked={ settings.includeVideoLinkInRsvpEmails }
			/>

			<ToggleControl
				className="classy-modal__section-text"
				__nextHasNoMarginBottom
				label={ _x( 'In Ticket emails', 'Setting label', 'event-tickets' ) }
				onChange={ ( newValue: boolean ): void =>
					setSettings( { ...settings, includeVideoLinkInTicketEmails: newValue } )
				}
				checked={ settings.includeVideoLinkInTicketEmails }
			/>
		</section>
	);
}
