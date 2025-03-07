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
import { InspectorControls } from '@wordpress/editor';
import { applyFilters } from '@wordpress/hooks';

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

/**
 * Get the block controls for the RSVP block.
 *
 * @since 5.20.0
 * @return {Array} The block controls.
 */
function getRSVPBlockControls() {
	const controls = [];

	/**
	 * Filters the RSVP block controls.
	 *
	 * @since 5.20.0
	 * @param {Array} controls The existing controls.
	 */
	return applyFilters( 'tec.tickets.blocks.RSVP.Controls', controls );
}

/**
 * The RSVP block controls.
 *
 * @since 5.20.0
 * @return {Node} The RSVP block controls.
 */
const RSVPControls = () => {
	const controls = getRSVPBlockControls();

	if ( ! controls.length ) {
		return null;
	}

	return <InspectorControls key="inspector">{controls}</InspectorControls>;
};

/**
 * The RSVP block.
 *
 * @param {Object} props The component properties.
 * @param {string} props.clientId The client ID of the block.
 * @param {boolean} props.created Whether the RSVP was created or not.
 * @param {boolean} props.hasRecurrenceRules Whether the event has recurrence rules.
 * @param {Function} props.initializeRSVP The function to initialize the RSVP.
 * @param {boolean} props.isAddEditOpen Whether the add/edit dashboard is open.
 * @param {boolean} props.isInactive Whether the RSVP is inactive.
 * @param {boolean} props.isLoading Whether the RSVP is loading.
 * @param {boolean} props.isModalShowing Whether the move modal is showing.
 * @param {boolean} props.isSelected Whether the RSVP is selected.
 * @param {boolean} props.isSettingsOpen Whether the settings dashboard is open.
 * @param {boolean} props.noRsvpsOnRecurring Whether there are no RSVPs on recurring events.
 * @param {number} props.rsvpId The RSVP ID.
 * @param {Function} props.setAddEditClosed The function to set the add/edit dashboard closed.
 * @return {Node} The RSVP block.
 */
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

		/**
		 * Filters the components injected before the header of the RSVP block.
		 *
		 * @since 5.20.0
		 * @return {Array} The injected components.
		 */
		const injectedComponentsTicketsBeforeHeader = applyFilters(
			'tec.tickets.blocks.RSVP.ComponentsBeforeHeader',
			[],
		);

		return (
			<div ref={ rsvpBlockRef }>
				{ injectedComponentsTicketsBeforeHeader }
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
				<RSVPControls />
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
