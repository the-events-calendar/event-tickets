import { LabeledItem, Select } from '@moderntribe/common/elements';
import PropTypes from 'prop-types';
import { Fragment } from 'react';
import { getLink, getLocalizedString } from '@tec/tickets/seating/utils';

const getString = (key) => getLocalizedString(key, 'capacity-form');

const EventLayoutSelect = ({
	layoutLocked,
	layouts,
	onLayoutChange,
	currentLayout,
	seatTypes,
	onSeatTypeChange,
	currentSeatType,
}) => {
	return (
		<Fragment>
			{ layoutLocked && (
				<div className="tec-tickets-seating-layouts-locked-info">
					{getString('seat-layout-label')}: <b>{currentLayout.label}</b>
				</div>
			)}

			<div style={{display: layoutLocked ? "none" : "block" }}>
				<Select
					id="tec-tickets-seating-layouts-select"
					placeholder={getString('event-layouts-select-placeholder')}
					options={layouts}
					onChange={onLayoutChange}
					value={currentLayout}
					isDisabled={layoutLocked}
				/>
			</div>

			{currentLayout && (
				<Select
					id="tec-tickets-seating-layouts-select"
					placeholder={getString('seat-types-select-placeholder')}
					options={seatTypes}
					onChange={onSeatTypeChange}
					value={currentSeatType}
				/>
			)}
			<div className="tec-tickets-seating-layouts-info">
				<span className="tec-tickets-seating-layouts-info-text">
					{getString('event-layouts-capacity-info')}
				</span>
				<a
					href={getLink('layouts')}
					target="_blank"
					className="button-link button-link--nested"
					rel="noreferrer"
				>
					{getString('view-layouts-link-label')}
				</a>
			</div>

		</Fragment>
	);
};

EventLayoutSelect.propTypes = {
	layoutLocked: PropTypes.bool,
	layouts: PropTypes.arrayOf(
		PropTypes.shape({
			label: PropTypes.string.isRequired,
			value: PropTypes.string.isRequired,
		})
	).isRequired,
	onLayoutChange: PropTypes.func.isRequired,
	currentLayout: PropTypes.shape({
		label: PropTypes.string.isRequired,
		value: PropTypes.string.isRequired,
	}),
	seatTypes: PropTypes.arrayOf(
		PropTypes.shape({
			label: PropTypes.string.isRequired,
			value: PropTypes.string.isRequired,
		})
	).isRequired,
	onSeatTypeChange: PropTypes.func.isRequired,
	currentSeatType: PropTypes.shape({
		label: PropTypes.string.isRequired,
		value: PropTypes.string.isRequired,
	}),
};

export default EventLayoutSelect;
