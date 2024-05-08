import { Fragment, useCallback } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { ToggleControl } from '@wordpress/components';
import { store, storeName } from '../store';
import PropTypes from 'prop-types';
import { LabeledItem, Select } from '@moderntribe/common/elements';
import './style.pcss';
import {
	setTicketCapacityInCommonStore,
	setTicketCapacityTypeInCommonStore,
	setTicketsSharedCapacityInCommonStore,
	setTicketsTempSharedCapacityInCommonStore,
	setTicketTempCapacityInCommonStore,
	setTicketTempCapacityTypeInCommonStore,
} from '../common-store-bridge';
import { SHARED } from '@moderntribe/tickets/data/blocks/ticket/constants';

const { getLink, getLocalizedString } = tec.seating.utils;

const getString = (key) => getLocalizedString(key, 'capacity-form');

const EventLayoutSelect = ({
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
				A
				isLabel={true}
			>
				<Select
					id="tec-tickets-seating-layouts-select"
					placeholder={getString('event-layouts-select-placeholder')}
					options={layouts}
					onChange={onLayoutChange}
					value={currentLayout}
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

const MemoizedEventLayoutSelect = React.memo(EventLayoutSelect);

export default function CapacityForm({ renderDefaultForm, clientId }) {
	const { setUsingAssignedSeating, setLayout, setTicketSeatType } =
		useDispatch(store);
	const getLayoutSeats = useSelect((select) => {
		return select(storeName).getLayoutSeats;
	}, []);
	const getSeatTypeSeats = useSelect((select) => {
		return select(storeName).getSeatTypeSeats;
	}, []);
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
			return select(storeName).getTicketSeatType(clientId);
		},
		[clientId]
	);

	const onToggleChange = useCallback(() => {
		setUsingAssignedSeating(!isUsingAssignedSeating);
	}, [isUsingAssignedSeating, setUsingAssignedSeating]);

	const seatTypes = useSelect(
		(select) => {
			return select(storeName).getSeatTypesForLayout(layout);
		},
		[layout]
	);

	const postType = useSelect(
		(select) => select('core/editor').getCurrentPostType(),
		[]
	);
	const [meta, setMeta] = useEntityProp('postType', postType, 'meta');
	const updateEventLayoutId = (layoutId) => {
		// WWID - not saving???
		setMeta({
			...meta,
			META_KEY_ENABLED: '1',
			META_KEY_LAYOUT_ID: layoutId,
		});
	};

	const onLayoutChange = useCallback(
		(choice) => {
			const layoutSeats = getLayoutSeats(choice.value);
			setTicketsSharedCapacityInCommonStore(layoutSeats);
			setTicketsTempSharedCapacityInCommonStore(layoutSeats);
			updateEventLayoutId(choice.value);
			setLayout(choice.value);
		},
		[getLayoutSeats, setLayout, updateEventLayoutId]
	);
	const onSeatTypeChange = useCallback(
		(choice) => {
			const seatTypeSeats = getSeatTypeSeats(choice.value);
			setTicketCapacityInCommonStore(clientId, seatTypeSeats);
			setTicketTempCapacityInCommonStore(clientId, seatTypeSeats);
			setTicketCapacityTypeInCommonStore(SHARED);
			setTicketTempCapacityTypeInCommonStore(SHARED);
			setTicketSeatType(clientId, choice.value);
		},
		[getSeatTypeSeats, setTicketSeatType, clientId]
	);

	const currentLayout =
		layouts && layout
			? layouts.find((layoutOption) => layoutOption.value === layout)
			: null;
	const currentSeatType =
		seatTypes && seatType
			? seatTypes.find(
					(seatTypeOption) => seatTypeOption.value === seatType
			  )
			: null;

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
					layouts={layouts}
					onLayoutChange={onLayoutChange}
					currentLayout={currentLayout}
					seatTypes={seatTypes}
					onSeatTypeChange={onSeatTypeChange}
					currentSeatType={currentSeatType}
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
