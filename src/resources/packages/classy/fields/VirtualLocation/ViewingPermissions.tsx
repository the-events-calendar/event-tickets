import * as React from 'react';
import { Fragment } from 'react';
import { _x } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { CheckboxControl, RadioControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { METADATA_EVENT_VIRTUAL_SHOW_EMBED_TO } from '../../constants.tsx';
import useMetaFiltering from './useMetaFiltering.ts';

export default function ViewingPermissions(): JSX.Element {
	const meta: {
		showAtRsvpAttendees: boolean;
		showAtTicketAttendees: boolean;
	} = useSelect( ( select ) => {
		const store: {
			getEditedPostAttribute: ( key: string ) => any;
		} = select( 'core/editor' );
		const meta = store.getEditedPostAttribute( 'meta' );
		const metaValue = meta[ METADATA_EVENT_VIRTUAL_SHOW_EMBED_TO ] ?? [];
		return {
			showAtRsvpAttendees: metaValue.includes( 'rsvp' ),
			showAtTicketAttendees: metaValue.includes( 'ticket' ),
		};
	}, [] );

	const [ showAtRsvpAttendees, setShowAtRsvpAttendees ] = useState< boolean >( meta.showAtRsvpAttendees );
	const [ showAtTicketAttendees, setShowAtTicketAttendees ] = useState< boolean >( meta.showAtTicketAttendees );
	const [ showOptions, setShowOptions ] = useState< boolean >( showAtRsvpAttendees || showAtTicketAttendees );

	useMetaFiltering(
		'viewing-permissions',
		( meta: Object ): Object => {
			let metaValue = meta[ METADATA_EVENT_VIRTUAL_SHOW_EMBED_TO ] ?? [];

			if ( showAtRsvpAttendees ) {
				metaValue.push( 'rsvp' );
			} else {
				metaValue = metaValue.filter( ( value: string ) => value !== 'rsvp' );
			}

			if ( showAtTicketAttendees ) {
				metaValue.push( 'ticket' );
			} else {
				metaValue = metaValue.filter( ( value: string ) => value !== 'ticket' );
			}

			meta[ METADATA_EVENT_VIRTUAL_SHOW_EMBED_TO ] = metaValue;

			return meta;
		},
		( meta: Object ): Object => {
			// No modification required, the default logic will remove the meta.
			return meta;
		},
		[ showAtRsvpAttendees, showAtTicketAttendees ]
	);

	return (
		<Fragment>
			<RadioControl
				style={ { position: 'relative', top: '-2px' } }
				className="classy-modal__section-text"
				label={ _x( 'Tickets viewing permissions', 'Virtual location setting label', 'event-tickets' ) }
				hideLabelFromVision={ true }
				options={ [
					{
						label: _x( 'Only attendees', 'Viewing permission option label', 'event-tickets' ),
						value: 'attendees',
					},
				] }
				selected={ showOptions ? 'attendees' : null }
				onChange={ (): void => {} }
				onClick={ (): void => {
					const newValue = ! showOptions;

					if ( ! newValue ) {
						setShowAtRsvpAttendees( false );
						setShowAtTicketAttendees( false );
					}

					setShowOptions( newValue );
				} }
			/>

			{ showOptions && (
				<div className="classy-toggle-indented classy-checkbox-list">
					<CheckboxControl
						__nextHasNoMarginBottom
						checked={ showAtRsvpAttendees }
						onChange={ ( newValue: boolean ): void => setShowAtRsvpAttendees( newValue ) }
						label={ _x( 'RSVP attendees', 'Viewing permission option label', 'event-tickets' ) }
					/>

					<CheckboxControl
						__nextHasNoMarginBottom
						checked={ showAtTicketAttendees }
						onChange={ ( newValue: boolean ): void => setShowAtTicketAttendees( newValue ) }
						label={ _x( 'Ticketed attendees', 'Viewing permission option label', 'event-tickets' ) }
					/>
				</div>
			) }
		</Fragment>
	);
}
