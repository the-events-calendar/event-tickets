/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import React, { useCallback } from 'react';

/**
 * WordPress dependencies
 */
import { Button, Popover } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import RSVPV2DurationFields from '../duration-fields/template';
import {
	DATE_PICKER_FOCUS_OUTSIDE_IGNORE_SELECTORS,
	isNestedDatePickerInteraction,
} from '../utils/is-nested-date-picker-interaction';
import './style.pcss';

const RSVPWindowPopover = ( { anchorRef, hasDurationError, isOpen, isSaving, onCancel, onSave } ) => {
	const handleDismiss = useCallback(
		( event ) => {
			if ( isNestedDatePickerInteraction( event ) ) {
				return;
			}

			onCancel();
		},
		[ onCancel ]
	);

	if ( ! isOpen || ! anchorRef?.current ) {
		return null;
	}

	return (
		<Popover
			anchor={ anchorRef.current }
			className="tribe-editor__rsvp-window-popover"
			data-unstable-ignore-focus-outside-for-relatedtarget={ DATE_PICKER_FOCUS_OUTSIDE_IGNORE_SELECTORS }
			focusOnMount={ false }
			onClose={ handleDismiss }
			onFocusOutside={ handleDismiss }
			position="bottom start"
		>
			<div className="tribe-editor__rsvp-window-popover__content">
				<h4 className="tribe-editor__rsvp-window-popover__title tribe-common-h6">
					{ __( 'RSVP Window', 'event-tickets' ) }
				</h4>
				<RSVPV2DurationFields autosave={ false } hasDurationError={ hasDurationError } />
				<div className="tribe-editor__rsvp-window-popover__actions">
					<Button isSecondary onClick={ onCancel }>
						{ __( 'Cancel', 'event-tickets' ) }
					</Button>
					<Button disabled={ hasDurationError } isPrimary isBusy={ isSaving } onClick={ onSave }>
						{ __( 'Save', 'event-tickets' ) }
					</Button>
				</div>
			</div>
		</Popover>
	);
};

RSVPWindowPopover.propTypes = {
	anchorRef: PropTypes.shape( { current: PropTypes.instanceOf( Element ) } ),
	hasDurationError: PropTypes.bool,
	isOpen: PropTypes.bool,
	isSaving: PropTypes.bool,
	onCancel: PropTypes.func.isRequired,
	onSave: PropTypes.func.isRequired,
};

export default RSVPWindowPopover;
