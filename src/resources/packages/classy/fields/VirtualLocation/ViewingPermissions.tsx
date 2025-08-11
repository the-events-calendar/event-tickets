import * as React from 'react';
import { Fragment } from 'react';
import { _x } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { CheckboxControl, RadioControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import {
	METADATA_EVENTS_VIRTUAL_RSVP_EMAIL_LINK,
	METADATA_EVENTS_VIRTUAL_TICKET_EMAIL_LINK,
} from '../../constants.tsx';
import { addFilter, hasFilter, removeFilter } from '@wordpress/hooks';

export default function ViewingPermissions(): JSX.Element {
	const meta: {
		showAtRsvpAttendees: boolean;
		showAtTicketAttendees: boolean;
	} = useSelect( ( select ) => {
		const store: {
			getEditedPostAttribute: ( key: string ) => any;
		} = select( 'core/editor' );
		const meta = store.getEditedPostAttribute( 'meta' );
		return {
			showAtRsvpAttendees: ( meta?.[ METADATA_EVENTS_VIRTUAL_RSVP_EMAIL_LINK ] ?? '' ) === 'yes',
			showAtTicketAttendees: ( meta?.[ METADATA_EVENTS_VIRTUAL_TICKET_EMAIL_LINK ] ?? '' ) === 'yes',
		};
	}, [] );

	const [ showAtTicketAttendees, setShowAtTicketAttendees ] = useState< boolean >( meta.showAtTicketAttendees );
	const [ showAtRsvpAttendees, setShowAtRsvpAttendees ] = useState< boolean >( meta.showAtRsvpAttendees );
	const [ showOptions, setShowOptions ] = useState< boolean >( showAtRsvpAttendees || showAtTicketAttendees );

	console.log( 'showOptions', showOptions );
	console.log( 'showAtTicketAttendees', showAtTicketAttendees );
	console.log( 'showAtRsvpAttendees', showAtRsvpAttendees );

	useEffect( () => {
		if ( hasFilter( 'tec.classy.events-pro.virtual-location.meta.update', 'tec.classy.event-tickets' ) ) {
			console.log( 'updateMeta.removingFilter' );

			removeFilter( 'tec.classy.events-pro.virtual-location.meta.update', 'tec.classy.event-tickets' );
		}

		console.log( 'updateMeta.addingFilter' );

		addFilter(
			'tec.classy.events-pro.virtual-location.meta.update',
			'tec.classy.event-tickets',
			( meta: Object ): Object => {
				meta[ METADATA_EVENTS_VIRTUAL_RSVP_EMAIL_LINK ] = showAtRsvpAttendees ? 'yes' : '';
				meta[ METADATA_EVENTS_VIRTUAL_TICKET_EMAIL_LINK ] = showAtTicketAttendees ? 'yes' : '';

				console.log( 'updateMeta', meta );

				return meta;
			}
		);

		if ( ! hasFilter( 'tec.classy.events-pro.virtual-location.meta.unset', 'tec.classy.event-tickets' ) ) {
			console.log( 'unsetMeta.addingFilter' );
			addFilter(
				'tec.classy.events-pro.virtual-location.meta.unset',
				'tec.classy.event-tickets',
				( meta: Object ): Object => {
					meta[ METADATA_EVENTS_VIRTUAL_RSVP_EMAIL_LINK ] = null;
					meta[ METADATA_EVENTS_VIRTUAL_TICKET_EMAIL_LINK ] = null;

					console.log( 'unsetMeta', meta );

					return meta;
				}
			);
		}
	}, [ showAtRsvpAttendees, showAtTicketAttendees ] );

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
					setShowOptions( ! showOptions );
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
