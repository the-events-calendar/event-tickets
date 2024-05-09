import { Fragment, useCallback } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { ToggleControl } from '@wordpress/components';
import { store, storeName } from '../store';
import PropTypes from 'prop-types';
import './style.pcss';
import {
	setTicketsSharedCapacityInCommonStore,
	setCappedTicketCapacityInCommonStore,
} from '../common-store-bridge';
import { META_KEY_ENABLED, META_KEY_LAYOUT_ID } from '../constants';
import EventLayoutSelect from './event-layout-select';

const { getLocalizedString } = tec.seating.utils;

const getString = (key) => getLocalizedString(key, 'capacity-form');

function getCurrentLayoutOption(layoutId, layouts) {
	return layouts && layoutId
		? layouts.find((layoutOption) => layoutOption.value === layoutId)
		: null;
}

function getCurrentSeatTypeOption(seatTypeId, seatTypes) {
	return seatTypes && seatTypeId
		? seatTypes.find(
				(seatTypeOption) => seatTypeOption.value === seatTypeId
		  )
		: null;
}

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
	const seatTypes = useSelect(
		(select) => {
			return select(storeName).getSeatTypesForLayout(layout);
		},
		[layout]
	);

	const isLayoutLocked = useSelect((select) => {
		return select(storeName).isLayoutLocked();
	}, []);

	const postType = useSelect(
		(select) => select('core/editor').getCurrentPostType(),
		[]
	);
	const postId = useSelect(
		(select) => select('core/editor').getCurrentPostId(),
		[]
	);

	const onToggleChange = useCallback(() => {
		if (isLayoutLocked) {
			return;
		}
		setUsingAssignedSeating(!isUsingAssignedSeating);
	}, [isLayoutLocked, isUsingAssignedSeating, setUsingAssignedSeating]);

	const [meta, setMeta] = useEntityProp('postType', postType, 'meta', postId);
	const updateEventMeta = useCallback(
		(layoutId) => {
			const newMeta = {
				...meta,
				[META_KEY_ENABLED]: '1',
				[META_KEY_LAYOUT_ID]: layoutId,
			};
			setMeta(newMeta);
		},
		[meta, setMeta]
	);

	const onLayoutChange = useCallback(
		(choice) => {
			const layoutSeats = getLayoutSeats(choice.value);
			setTicketsSharedCapacityInCommonStore(layoutSeats);
			updateEventMeta(choice.value);
			setLayout(choice.value);
		},
		[getLayoutSeats, setLayout, updateEventMeta]
	);

	const onSeatTypeChange = useCallback(
		(choice) => {
			const seatTypeSeats = getSeatTypeSeats(choice.value);
			setCappedTicketCapacityInCommonStore(clientId, seatTypeSeats);
			setTicketSeatType(clientId, choice.value);
		},
		[getSeatTypeSeats, setTicketSeatType, clientId]
	);

	return (
		<Fragment>
			<ToggleControl
				className="tec-tickets-seating__capacity-form__toggle"
				label={getString('use-assigned-seating-toggle-label')}
				checked={isUsingAssignedSeating}
				onChange={onToggleChange}
				disabled={isLayoutLocked}
			/>
			{isUsingAssignedSeating ? (
				<MemoizedEventLayoutSelect
					layoutLocked={isLayoutLocked}
					layouts={layouts}
					onLayoutChange={onLayoutChange}
					currentLayout={getCurrentLayoutOption(layout, layouts)}
					seatTypes={seatTypes}
					onSeatTypeChange={onSeatTypeChange}
					currentSeatType={getCurrentSeatTypeOption(
						seatType,
						seatTypes
					)}
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
