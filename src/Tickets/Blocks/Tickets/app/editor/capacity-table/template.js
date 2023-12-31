/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { NumberInput } from '@moderntribe/common/elements';
import Row from './row/template';
import './style.pcss';

const CapacityTable = ({
	independentCapacity,
	independentTicketItems,
	isSettingsLoading,
	onSharedCapacityChange,
	sharedCapacity,
	sharedTicketItems,
	totalCapacity,
	unlimitedTicketItems,
	rowsAfter,
}) => {
	const sharedInput = (
		<NumberInput
			onChange={onSharedCapacityChange}
			value={sharedCapacity}
			disabled={isSettingsLoading}
			min={0}
		/>
	);

	return (
		<div className="tribe-editor__tickets__capacity-table">
			<h3 className="tribe-editor__tickets__capacity-table-title">
				{__('Capacity', 'event-tickets')}
			</h3>
			<Row
				label={__('Shared capacity', 'event-tickets')}
				items={sharedTicketItems}
				right={sharedInput}
			/>
			<Row
				label={__('Independent capacity', 'event-tickets')}
				items={independentTicketItems}
				right={independentCapacity}
			/>
			{unlimitedTicketItems.length > 0 && (
				<Row
					label={__('Unlimited capacity', 'event-tickets')}
					items={unlimitedTicketItems}
					right={__('Unlimited', 'event-tickets')}
				/>
			)}

			{rowsAfter &&
				rowsAfter.map((row, index) => {
					return (
						<Row
							key={index}
							label={row.label || ''}
							items={row.items || ''}
							right={row.right || ''}
						/>
					);
				})}

			<Row
				label={__('Total Capacity', 'event-tickets')}
				right={totalCapacity}
			/>
		</div>
	);
};

CapacityTable.propTypes = {
	independentCapacity: PropTypes.number,
	independentTicketItems: PropTypes.string,
	isSettingsLoading: PropTypes.bool,
	onSharedCapacityChange: PropTypes.func,
	rowsAfter: PropTypes.arrayOf(
		PropTypes.shape({
			label: PropTypes.string,
			items: PropTypes.string,
			right: PropTypes.node,
		})
	),
	sharedCapacity: PropTypes.string,
	sharedTicketItems: PropTypes.string,
	totalCapacity: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
	unlimitedTicketItems: PropTypes.string,
};

export default CapacityTable;
