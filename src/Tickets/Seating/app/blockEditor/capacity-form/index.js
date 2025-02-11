import { useCallback } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { RadioControl } from '@wordpress/components';
import { store, storeName } from '../store';
import PropTypes from 'prop-types';
import './style.pcss';
import {
	setTicketsSharedCapacityInCommonStore,
	setCappedTicketCapacityInCommonStore,
} from '../store/common-store-bridge';
import { META_KEY_ENABLED, META_KEY_LAYOUT_ID } from '../constants';
import EventLayoutSelect from './event-layout-select';
import ServiceError from './service-error';
import { getLocalizedString } from '@tec/tickets/seating/utils';
import SeriesNotice from "./series-notice";

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
const MemoizedServiceError = React.memo(ServiceError);

export default function CapacityForm({ renderDefaultForm, clientId }) {
	const {
		setUsingAssignedSeating,
		setLayout,
		setEventCapacity,
		setTicketSeatType,
	} = useDispatch(store);
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

	const isServiceStatusOk = useSelect((select) => {
		return select(storeName).isServiceStatusOk();
	}, []);

	const serviceStatus = useSelect((select) => {
		return select(storeName).getServiceStatus();
	}, []);

	const serviceConnectUrl = useSelect((select) => {
		return select(storeName).getServiceConnectUrl();
	}, []);

	const postType = useSelect(
		(select) => select('core/editor').getCurrentPostType(),
		[]
	);
	const postId = useSelect(
		(select) => select('core/editor').getCurrentPostId(),
		[]
	);

	const [meta, setMeta] = useEntityProp('postType', postType, 'meta', postId);
	const updateEventMeta = useCallback(
		(layoutId) => {
			if (true === layoutId) {
				const newMeta = {
					...meta,
					// We leave [META_KEY_LAYOUT_ID] as it was since that hasn't changed yet.
					[META_KEY_ENABLED]: '1',
				};
				setMeta(newMeta);
				return;
			}

			if (false === layoutId) {
				const newMeta = {
					...meta,
					[META_KEY_ENABLED]: '0',
					// We set [META_KEY_LAYOUT_ID] to an empty string since we're disabling assigned seating.
					[META_KEY_LAYOUT_ID]: '',
				};
				setMeta(newMeta);
				return;
			}

			const newMeta = {
				...meta,
				[META_KEY_ENABLED]: '1',
				[META_KEY_LAYOUT_ID]: layoutId,
			};
			setMeta(newMeta);
		},
		[meta, setMeta]
	);

	const onToggleChange = useCallback(
		(value) => {
			if (isLayoutLocked) {
				return;
			}

			setUsingAssignedSeating(value === 'seat');
			updateEventMeta(value === 'seat');
		},
		[isLayoutLocked, setUsingAssignedSeating, updateEventMeta]
	);

	const onLayoutChange = useCallback(
		(choice) => {
			const layoutSeats = getLayoutSeats(choice.value);
			updateEventMeta(choice.value);
			setLayout(choice.value);
			setEventCapacity(layoutSeats);
			setTicketSeatType(clientId, null);
			setCappedTicketCapacityInCommonStore(clientId, 0);
			setTicketsSharedCapacityInCommonStore(clientId, layoutSeats);
		},
		[getLayoutSeats, setEventCapacity, setLayout, updateEventMeta, clientId]
	);

	const onSeatTypeChange = useCallback(
		(choice) => {
			const seatTypeSeats = getSeatTypeSeats(choice.value);
			setTicketSeatType(clientId, choice.value);
			setCappedTicketCapacityInCommonStore(clientId, seatTypeSeats);
		},
		[getSeatTypeSeats, setTicketSeatType, clientId]
	);

	const renderLayoutSelect = () => {
		const inSeries = window?.TECFtEditorData?.event?.isInSeries || false;

		if ( inSeries ) {
			return ( <SeriesNotice /> );
		}

		return isServiceStatusOk ? (
			<MemoizedEventLayoutSelect
				layoutLocked={isLayoutLocked}
				layouts={layouts}
				onLayoutChange={onLayoutChange}
				currentLayout={getCurrentLayoutOption(layout, layouts)}
				seatTypes={seatTypes}
				onSeatTypeChange={onSeatTypeChange}
				currentSeatType={getCurrentSeatTypeOption(seatType, seatTypes)}
			/>
		) : (
			<MemoizedServiceError
				status={serviceStatus}
				serviceConnectUrl={serviceConnectUrl}
			/>
		);
	};

	return (
		<div className="tec-tickets-seating__capacity-form">
			{isLayoutLocked ? (
				<div className="tec-tickets-seating__capacity-locked-info">
					{getString(
						isUsingAssignedSeating
							? 'seat-option-label'
							: 'general-admission-label'
					)}
				</div>
			) : (
				<RadioControl
					className="tec-tickets-seating__capacity-radio"
					onChange={onToggleChange}
					options={[
						{
							label: getString('general-admission-label'),
							value: 'regular',
						},
						{
							label: getString('seat-option-label'),
							value: 'seat',
						},
					]}
					selected={isUsingAssignedSeating ? 'seat' : 'regular'}
				/>
			)}

			{isUsingAssignedSeating
				? renderLayoutSelect()
				: renderDefaultForm()}
		</div>
	);
}

CapacityForm.propTypes = {
	renderDefaultForm: PropTypes.func.isRequired,
	ticketPostId: PropTypes.number,
};
