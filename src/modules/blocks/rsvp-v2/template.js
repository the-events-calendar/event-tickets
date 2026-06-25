/**
 * V2 RSVP Template
 *
 * Autosaves RSVP changes via REST. The create form opens on "Add RSVP" and
 * persists field edits without an explicit save button.
 */

/**
 * External dependencies
 */
import * as React from 'react';
import { useCallback, useEffect, useRef } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { Spinner } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import RSVPContainer from './container-panel/container';
import RSVPInactiveBlock from './inactive-block/container';
import MoveModal from '../../elements/move-modal';
import { Card } from '../../elements';
import RSVPSidebarControls from './sidebar-controls/container';
import { isSavedSummary } from './utils/block-state';
import { RSVPControls } from '../rsvp-shared/utils/block-controls';
import { renderBlockNotSupported } from '../rsvp-shared/utils/not-supported';
import {
	useCloseOverlaysOnDeselect,
	isRsvpOverlayClick,
} from '../rsvp-shared/utils/close-overlays';
import '../rsvp-shared/style.pcss';

/**
 * The V2 RSVP block template.
 *
 * @param {Object}   props                    The component properties.
 * @param {string}   props.clientId           The client ID of the block.
 * @param {boolean}  props.created            Whether the RSVP was created or not.
 * @param {boolean}  props.hasRecurrenceRules Whether the event has recurrence rules.
 * @param {Function} props.initializeRSVP     The function to initialize the RSVP.
 * @param {boolean}  props.isAddEditOpen      Whether the add/edit dashboard is open.
 * @param {boolean}  props.isLoading          Whether the RSVP is loading.
 * @param {boolean}  props.isModalShowing     Whether the move modal is showing.
 * @param {boolean}  props.isSelected         Whether the RSVP is selected.
 * @param {boolean}  props.noRsvpsOnRecurring Whether there are no RSVPs on recurring events.
 * @param {number}   props.rsvpId             The RSVP ID.
 * @param {Function} props.closeBlockOverlays Closes every open RSVP overlay.
 * @param {Function} props.closeBlockOverlaysOnDeselect Closes popovers when the block is deselected.
 * @return {Node} The V2 RSVP block.
 */
const RSVPV2 = ( {
	clientId,
	created,
	hasRecurrenceRules,
	initializeRSVP,
	isAddEditOpen,
	isLoading,
	isModalShowing,
	isSelected,
	noRsvpsOnRecurring,
	rsvpId,
	closeBlockOverlays,
	closeBlockOverlaysOnDeselect,
} ) => {
	const rsvpBlockRef = useRef( null );

	const handleOutsideBlockClick = useCallback(
		( event ) => {
			if ( isRsvpOverlayClick( event.target ) ) {
				return;
			}

			const rsvpButtons = [ 'add-rsvp', 'attendees-rsvp', 'settings-rsvp' ];

			if (
				rsvpBlockRef.current &&
				! rsvpBlockRef.current.contains( event.target ) &&
				! rsvpButtons.includes( event.target.id )
			) {
				closeBlockOverlays();
			}
		},
		[ closeBlockOverlays ]
	);

	useCloseOverlaysOnDeselect( isSelected, closeBlockOverlaysOnDeselect );

	useEffect( () => {
		! rsvpId && initializeRSVP();
		document.addEventListener( 'click', handleOutsideBlockClick );

		return () => document.removeEventListener( 'click', handleOutsideBlockClick );
	}, [ handleOutsideBlockClick, initializeRSVP, rsvpId ] );

	const renderBlock = () => {
		/**
		 * Filters the components injected before the header of the RSVP block.
		 *
		 * @since 5.20.0
		 * @return {Array} The injected components.
		 */
		const injectedComponentsTicketsBeforeHeader = applyFilters(
			'tec.tickets.blocks.RSVP.ComponentsBeforeHeader',
			[]
		);

		// Show the inactive "Add RSVP" prompt when the create form is closed
		// and no ticket exists yet.
		const displayInactive = ! isAddEditOpen && ! created;
		const savedSummary = isSavedSummary( { created, isAddEditOpen } );

		const blockClassName = classNames(
			'tribe-editor__rsvp',
			{ 'tribe-editor__rsvp--add-edit-open': isAddEditOpen },
			{ 'tribe-editor__rsvp--selected': isSelected },
			{ 'tribe-editor__rsvp--loading': isLoading }
		);

		const blockBody = (
			<>
				<RSVPContainer isSelected={ isSelected } clientId={ clientId } />
				{ isLoading && <Spinner /> }
			</>
		);

		return (
			<div ref={ rsvpBlockRef }>
				{ injectedComponentsTicketsBeforeHeader }
				{ displayInactive ? (
					<RSVPInactiveBlock />
				) : savedSummary ? (
					<div className={ blockClassName }>{ blockBody }</div>
				) : (
					<Card className={ blockClassName }>{ blockBody }</Card>
				) }
				{ isModalShowing && <MoveModal /> }
				<RSVPControls />
				{ isSelected && <RSVPSidebarControls /> }
			</div>
		);
	};

	if ( hasRecurrenceRules && noRsvpsOnRecurring ) {
		return renderBlockNotSupported( clientId );
	}

	return renderBlock();
};

RSVPV2.propTypes = {
	clientId: PropTypes.string.isRequired,
	created: PropTypes.bool.isRequired,
	hasRecurrenceRules: PropTypes.bool.isRequired,
	initializeRSVP: PropTypes.func.isRequired,
	isAddEditOpen: PropTypes.bool.isRequired,
	isLoading: PropTypes.bool.isRequired,
	isModalShowing: PropTypes.bool.isRequired,
	isSelected: PropTypes.bool.isRequired,
	noRsvpsOnRecurring: PropTypes.bool.isRequired,
	rsvpId: PropTypes.number.isRequired,
	closeBlockOverlays: PropTypes.func.isRequired,
	closeBlockOverlaysOnDeselect: PropTypes.func.isRequired,
};

export default RSVPV2;
