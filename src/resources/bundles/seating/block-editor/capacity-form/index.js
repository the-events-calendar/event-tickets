import { Fragment, useCallback } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { ToggleControl } from '@wordpress/components';
import { store, storeName } from '../store';
import PropTypes from 'prop-types';
import { LabeledItem, Select } from '@moderntribe/common/elements';
import './style.pcss';
import { useState, useEffect } from 'react';

const { getLink, getLocalizedString } = tec.seating.utils;
const { fetchSeatTypesByLayoutId } = tec.seating.ajax;

const getString = (key) => getLocalizedString(key, 'capacity-form');

const EventLayoutSelect = ({
	layouts,
	seatTypes,
	setLayout,
	layout,
	setSeatType,
	seatType,
}) => {
	return (
		<Fragment>
			<LabeledItem
				className="tribe-editor__labeled-select-input tribe-editor__labeled-select-input--nested"
				label={getString('event-layouts-select-label')}
				for="tec-tickets-seating-layouts-select"
				A
				isLabel={true}
			>
				<Select
					id="tec-tickets-seating-layouts-select"
					placeholder={getString('event-layouts-select-placeholder')}
					options={layouts}
					onChange={(value) => setLayout(value)}
					value={layout}
				/>
			</LabeledItem>

			{layout && (
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
						onChange={(value) => setSeatType(value)}
						value={seatType}
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
	layouts: PropTypes.arrayOf(
		PropTypes.shape({
			label: PropTypes.string.isRequired,
			value: PropTypes.string.isRequired,
		})
	).isRequired,
	seatTypes: PropTypes.arrayOf(
		PropTypes.shape({
			label: PropTypes.string.isRequired,
			value: PropTypes.string.isRequired,
		})
	).isRequired,
	setLayout: PropTypes.func.isRequired, // eslint-disable-line react/no-unused-prop-types
	layout: PropTypes.string.isRequired,
	ticketBlockClientId: PropTypes.number.isRequired,
	setSeatType: PropTypes.func.isRequired,
	seatType: PropTypes.string.isRequired,
};

const MemoizedEventLayoutSelect = React.memo(EventLayoutSelect);

export default function CapacityForm({
	renderDefaultForm,
	ticketBlockClientId,
}) {
	const { setUsingAssignedSeating, setLayout, setSeatType } =
		useDispatch(store);
	const isUsingAssignedSeating = useSelect((select) => {
		return select(storeName).isUsingAssignedSeating();
	}, []);
	const layouts = useSelect((select) => {
		return select(storeName).getLayoutsInOptionFormat();
	}, []);
	const layout = useSelect((select) => {
		return select(storeName).getCurrentLayoutId();
	}, []);
	const seatType = useSelect(
		(select) => {
			return select(storeName).getCurrentSeatTypeId(ticketBlockClientId);
		},
		[ticketBlockClientId]
	);
	const onToggleChange = useCallback(() => {
		setUsingAssignedSeating(!isUsingAssignedSeating);
	}, [isUsingAssignedSeating, setUsingAssignedSeating]);

	const [seatTypes, setSeatTypes] = useState([]);

	useEffect(() => {
		fetchSeatTypesByLayoutId(layout)
			.then((fetchedSeatTypes) => setSeatTypes(fetchedSeatTypes))
			.catch((error) => {
				console.error(error);
			});
	}, [layout]);

	return (
		<Fragment>
			<ToggleControl
				className="tec-tickets-seating__capacity-form__toggle"
				label={getString('use-assigned-seating-toggle-label')}
				checked={isUsingAssignedSeating}
				onChange={onToggleChange}
			/>
			{isUsingAssignedSeating ? (
				<MemoizedEventLayoutSelect
					ticketBlockClientId={ticketBlockClientId}
					layouts={layouts}
					seatTypes={seatTypes}
					setLayout={setLayout}
					layout={layout}
					setSeatType={(value) =>
						setSeatType(ticketBlockClientId, value)
					}
					seatType={seatType}
				/>
			) : (
				renderDefaultForm()
			)}
		</Fragment>
	);
}

CapacityForm.propTypes = {
	renderDefaultForm: PropTypes.func.isRequired,
	ticketPostId: PropTypes.number.isRequired,
};
