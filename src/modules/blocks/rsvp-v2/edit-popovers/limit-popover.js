/**
 * External dependencies
 */
import React, { useCallback, useState } from 'react';
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Popover, Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { NumberInput } from '@moderntribe/common/elements';
import './style.pcss';

const RSVPLimitPopover = ( { anchorRef, isOpen, isSaving, onCancel, onSave, onTempCapacityChange, tempCapacity } ) => {
	if ( ! isOpen ) {
		return null;
	}

	return (
		<Popover
			anchor={ anchorRef?.current }
			className="tribe-editor__rsvp-limit-popover"
			onClose={ onCancel }
			onFocusOutside={ onCancel }
			position="bottom center"
		>
			<div className="tribe-editor__rsvp-limit-popover__content">
				<h4 className="tribe-editor__rsvp-limit-popover__title tribe-common-h6">
					{ __( 'RSVP Limit', 'event-tickets' ) }
				</h4>
				<NumberInput
					className="tribe-editor__rsvp-limit-popover__input"
					min={ 0 }
					onChange={ onTempCapacityChange }
					value={ tempCapacity }
				/>
				<p className="tribe-editor__rsvp-limit-popover__help tribe-common-b3 tribe-common-h--alt">
					{ __( 'Leave blank for unlimited', 'event-tickets' ) }
				</p>
				<div className="tribe-editor__rsvp-limit-popover__actions">
					<Button isSecondary onClick={ onCancel }>
						{ __( 'Cancel', 'event-tickets' ) }
					</Button>
					<Button isPrimary isBusy={ isSaving } onClick={ onSave }>
						{ __( 'Save', 'event-tickets' ) }
					</Button>
				</div>
			</div>
		</Popover>
	);
};

RSVPLimitPopover.propTypes = {
	anchorRef: PropTypes.shape( { current: PropTypes.instanceOf( Element ) } ),
	isOpen: PropTypes.bool,
	isSaving: PropTypes.bool,
	onCancel: PropTypes.func.isRequired,
	onSave: PropTypes.func.isRequired,
	onTempCapacityChange: PropTypes.func.isRequired,
	tempCapacity: PropTypes.string,
};

export default RSVPLimitPopover;
