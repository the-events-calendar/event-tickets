/**
 * External dependencies
 */
import React, { useCallback, useEffect, useRef } from 'react';
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
import RSVPContainer from './container/container';
import RSVPInactiveBlock from './inactive-block/container';
import MoveModal from '../../elements/move-modal';
import { Card } from '../../elements';
import { RSVPControls } from '../rsvp-shared/utils/block-controls';
import { renderBlockNotSupported } from '../rsvp-shared/utils/not-supported';
import {
	useCloseOverlaysOnDeselect,
	isRsvpOverlayClick,
} from '../rsvp-shared/utils/close-overlays';
import '../rsvp-shared/style.pcss';

/**
 * The RSVP block.
 *
 * @param {Object}   props                    The component properties.
 * @param {string}   props.clientId           The client ID of the block.
 * @param {boolean}  props.created            Whether the RSVP was created or not.
 * @param {boolean}  props.hasRecurrenceRules Whether the event has recurrence rules.
 * @param {Function} props.initializeRSVP     The function to initialize the RSVP.
 * @param {boolean}  props.isAddEditOpen      Whether the add/edit dashboard is open.
 * @param {boolean}  props.isInactive         Whether the RSVP is inactive.
 * @param {boolean}  props.isLoading          Whether the RSVP is loading.
 * @param {boolean}  props.isModalShowing     Whether the move modal is showing.
 * @param {boolean}  props.isSelected         Whether the RSVP is selected.
 * @param {boolean}  props.isSettingsOpen     Whether the settings dashboard is open.
 * @param {boolean}  props.noRsvpsOnRecurring Whether there are no RSVPs on recurring events.
 * @param {number}   props.rsvpId             The RSVP ID.
 * @param {Function} props.closeBlockOverlays Closes every open RSVP overlay.
 * @param {Function} props.closeBlockOverlaysOnDeselect Closes popovers when the block is deselected.
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
	closeBlockOverlays,
	closeBlockOverlaysOnDeselect,
} ) => {
	const rsvpBlockRef = useRef( null );

	const handleOutsideBlockClick = useCallback(
		( event ) => {
			if ( isRsvpOverlayClick( event.target ) ) {
				return;
			}

			const rsvpButtons = [ 'add-rsvp', 'edit-rsvp', 'attendees-rsvp', 'settings-rsvp' ];

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
		const displayInactive = ! isAddEditOpen && ( ( created && isInactive ) || ! created );

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

		const cardChildren = applyFilters( 'tec.tickets.blocks.RSVP.CardChildren', [], {
			isAddEditOpen,
			clientId,
			isLoading,
		} );

		const blockPanels = applyFilters( 'tec.tickets.blocks.RSVP.BlockPanels', [], {
			isSettingsOpen,
			clientId,
		} );

		return (
			<div ref={ rsvpBlockRef }>
				{ injectedComponentsTicketsBeforeHeader }
				{ displayInactive ? (
					<RSVPInactiveBlock />
				) : (
					! isSettingsOpen && (
						<Card
							className={ classNames(
								'tribe-editor__rsvp',
								'tribe-editor__rsvp-v1',
								{ 'tribe-editor__rsvp--add-edit-open': isAddEditOpen },
								{ 'tribe-editor__rsvp--selected': isSelected },
								{ 'tribe-editor__rsvp--loading': isLoading }
							) }
						>
							<RSVPContainer isSelected={ isSelected } clientId={ clientId } />
							{ cardChildren }
							{ isLoading && <Spinner /> }
						</Card>
					)
				) }
				{ blockPanels }
				{ isModalShowing && <MoveModal /> }
				<RSVPControls />
			</div>
		);
	};

	if ( hasRecurrenceRules && noRsvpsOnRecurring ) {
		return renderBlockNotSupported( clientId );
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
	closeBlockOverlays: PropTypes.func.isRequired,
	closeBlockOverlaysOnDeselect: PropTypes.func.isRequired,
};

export default RSVP;
