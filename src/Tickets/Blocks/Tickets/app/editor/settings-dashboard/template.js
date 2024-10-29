/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Wordpress dependencies
 */
import { Spinner } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { SettingsDashboard } from '@moderntribe/tickets/elements';
import CapacityTable from '../capacity-table/container';
import HeaderImage from '../header-image/container';
import './style.pcss';

const TicketsSettingsDashboard = ( {
	hasTicketsPlus,
	isSettingsLoading,
	onCloseClick,
} ) =>  {
	/**
	 * Filters the settings fields.
	 *
	 * @since 5.16.0
	 *
	 * @param {Array} fields The settings fields.
	 * @param {Object} props The component props.
	 */
	const settingsFields = applyFilters(
		'tec.tickets.blocks.Tickets.Settings.Fields',
		[],
		{ hasTicketsPlus, isSettingsLoading },
	);

	return (
		<SettingsDashboard
			className={ classNames(
				'tribe-editor__tickets__settings-dashboard',
				{ 'tribe-editor__tickets__settings-dashboard--loading': isSettingsLoading },
			) }
			closeButtonDisabled={ isSettingsLoading }
			content={ (
				<Fragment>
					{ hasTicketsPlus && <CapacityTable /> }
					{ settingsFields.map( ( field, index) => <Fragment key={index}>{field}</Fragment> ) }
					<HeaderImage />
					{ isSettingsLoading && <Spinner /> }
				</Fragment>
			) }
			onCloseClick={ onCloseClick }
		/>
	);
};

TicketsSettingsDashboard.propTypes = {
	hasTicketsPlus: PropTypes.bool,
	isSettingsLoading: PropTypes.bool.isRequired,
	onCloseClick: PropTypes.func.isRequired,
};

export default TicketsSettingsDashboard;
