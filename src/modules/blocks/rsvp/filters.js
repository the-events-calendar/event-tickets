/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import MoveDelete from './move-delete/container';
import RSVPActionDashboard from './action-dashboard/container';
import RSVPSettingsDashboard from './settings-dashboard/container';

addFilter( 'tec.tickets.blocks.RSVP.ActionDashboardActions', 'event-tickets/rsvp-v1', ( actions, { clientId, created } ) => {
	if ( ! created ) {
		return actions;
	}

	return [ ...actions, <MoveDelete key="rsvp-move-delete" clientId={ clientId } /> ];
} );

addFilter( 'tec.tickets.blocks.RSVP.CardChildren', 'event-tickets/rsvp-v1', ( children, { isAddEditOpen, clientId } ) => {
	if ( ! isAddEditOpen ) {
		return children;
	}

	return [ ...children, <RSVPActionDashboard key="rsvp-action-dashboard" clientId={ clientId } /> ];
} );

addFilter( 'tec.tickets.blocks.RSVP.BlockPanels', 'event-tickets/rsvp-v1', ( panels, { isSettingsOpen } ) => {
	if ( ! isSettingsOpen ) {
		return panels;
	}

	return [ ...panels, <RSVPSettingsDashboard key="rsvp-settings-dashboard" /> ];
} );
