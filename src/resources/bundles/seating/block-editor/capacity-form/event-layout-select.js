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
			<LabeledItem
				className="tribe-editor__labeled-select-input tribe-editor__labeled-select-input--nested"
				label={getString('event-layouts-select-label')}
				for="tec-tickets-seating-layouts-select"
				isLabel={true}
			>
				<Select
					id="tec-tickets-seating-layouts-select"
					placeholder={getString('event-layouts-select-placeholder')}
					options={layouts}
					onChange={onLayoutChange}
					value={currentLayout}
					isDisabled={layoutLocked}
				/>
			</LabeledItem>

			{currentLayout && (
				<LabeledItem
					className="tribe-editor__labeled-select-input tribe-editor__labeled-select-input--nested"
					label={getString('seat-types-select-label')}
					for="tec-tickets-seating-seat-types-select"
					A
					isLabel={true}
				>
					<Select
						id="tec-tickets-seating-layouts-select"
						placeholder={getString('seat-types-select-placeholder')}
						options={seatTypes}
						onChange={onSeatTypeChange}
						value={currentSeatType}
					/>
				</LabeledItem>
			)}

			<a
				href={getLink('layouts')}
				target="_blank"
				className="button-link button-link--nested"
				rel="noreferrer"
			>
				{getString('view-layouts-link-label')}
			</a>
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
