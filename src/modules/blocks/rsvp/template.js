/**
 * External dependencies
 */
import React, { useCallback, useEffect, useRef } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { Spinner, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import RSVPContainer from './container/container';
import RSVPActionDashboard from '@moderntribe/tickets/blocks/rsvp/action-dashboard/container';
import RSVPSettingsDashboard from '@moderntribe/tickets/blocks/rsvp/settings-dashboard/container';
import RSVPInactiveBlock from './inactive-block/container';
import MoveModal from '@moderntribe/tickets/elements/move-modal';
import { Card } from '@moderntribe/tickets/elements';
import './style.pcss';

const RSVP = ( {
	clientId,
	created,
	hasRecurrenceRules,
	initializeRSVP,
	isAddEditOpen,
	isInactive,
	isLoading,
	isModalShowing,
	isSelected,
	isSettingsOpen,
	noRsvpsOnRecurring,
	rsvpId,
	setAddEditClosed,
} ) => {
	const rsvpBlockRef = useRef( null );

	const handleAddEditClose = useCallback( ( event ) => {
		const rsvpButtons = [ 'add-rsvp', 'edit-rsvp', 'attendees-rsvp', 'settings-rsvp' ];

		if (
			rsvpBlockRef.current &&
			! rsvpBlockRef.current.contains( event.target ) &&
			! rsvpButtons.includes( event.target.id )
		) {
			setAddEditClosed();
		}
	}, [ setAddEditClosed ] );

	useEffect( () => {
		! rsvpId && initializeRSVP();
		document.addEventListener( 'click', handleAddEditClose );

		return () => document.removeEventListener( 'click', handleAddEditClose );
	}, [ handleAddEditClose, initializeRSVP, rsvpId ] );

	const renderBlock = () => {
		const displayInactive = ! isAddEditOpen && ( ( created && isInactive ) || ! created );

		return (
			<div ref={ rsvpBlockRef }>
				{
					displayInactive
						? <RSVPInactiveBlock />
						: (
							! isSettingsOpen &&
							(
								<Card className={
									classNames(
										'tribe-editor__rsvp',
										{ 'tribe-editor__rsvp--add-edit-open': isAddEditOpen },
										{ 'tribe-editor__rsvp--selected': isSelected },
										{ 'tribe-editor__rsvp--loading': isLoading },
									) }
								>
									<RSVPContainer isSelected={ isSelected } clientId={ clientId } />
									{ isAddEditOpen && <RSVPActionDashboard clientId={ clientId } /> }
									{ isLoading && <Spinner /> }
								</Card>
							)
						)
				}
				{ isSettingsOpen && <RSVPSettingsDashboard />}
				{ isModalShowing && <MoveModal /> }
			</div>
		);
	};

	const renderBlockNotSupported = () => {
		return (
			<div className="tribe-editor__not-supported-message">
				<p className="tribe-editor__not-supported-message-text">
					{ __( 'RSVPs are not yet supported on recurring events.', 'event-tickets' ) }
					<br />
					<a
						className="tribe-editor__not-supported-message-link"
						href="https://evnt.is/1b7a"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Read about our plans for future features.', 'event-tickets' ) }
					</a>
					<br />
					<Button variant="secondary" onClick={ () =>
						wp.data.dispatch( 'core/block-editor' ).removeBlock( clientId ) }
					>
						{ __( 'Remove block', 'event-tickets' ) }
					</Button>
				</p>
			</div>
		);
	};

	if ( hasRecurrenceRules && noRsvpsOnRecurring ) {
		return renderBlockNotSupported();
	}

	return renderBlock();
};

RSVP.propTypes = {
	clientId: PropTypes.string.isRequired,
	created: PropTypes.bool.isRequired,
	hasRecurrenceRules: PropTypes.bool.isRequired,
	initializeRSVP: PropTypes.func.isRequired,
	isAddEditOpen: PropTypes.bool.isRequired,
	isInactive: PropTypes.bool.isRequired,
	isLoading: PropTypes.bool.isRequired,
	isModalShowing: PropTypes.bool.isRequired,
	isSelected: PropTypes.bool.isRequired,
	isSettingsOpen: PropTypes.bool.isRequired,
	noRsvpsOnRecurring: PropTypes.bool.isRequired,
	rsvpId: PropTypes.number.isRequired,
	setAddEditClosed: PropTypes.func.isRequired,
};

export default RSVP;
