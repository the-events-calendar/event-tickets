import {Fragment, useCallback} from '@wordpress/element';
import {useDispatch, useSelect} from '@wordpress/data';
import {ToggleControl} from '@wordpress/components';
import {store, storeName} from '../store';
import PropTypes from 'prop-types';
import {LabeledItem, Select} from '@moderntribe/common/elements';
import './style.pcss';

const {getLink, getLocalizedString} = tec.seating.utils;

const getString = (key) => getLocalizedString(key, 'capacity-form');

const EventLayoutSelect = ({layouts, seatTypes}) => {
	return (
		<Fragment>
			<LabeledItem
				className="tribe-editor__labeled-select-input tribe-editor__labeled-select-input--nested"
				label={getString('event-layouts-select-label')}
				for="tec-events-assigned-seating-layouts-select" A
				isLabel={true}
			>
				<Select
					id="tec-events-assigned-seating-layouts-select"
					placeholder={getString('event-layouts-select-placeholder')}
					options={layouts}
					onChange={(value)=>setLayout(value)}
					value={null}
				/>
			</LabeledItem>

			<LabeledItem
				className="tribe-editor__labeled-select-input tribe-editor__labeled-select-input--nested"
				label={getString('seat-types-select-label')}
				for="tec-events-assigned-seating-seat-types-select" A
				isLabel={true}
			>
				<Select
					id="tec-events-assigned-seating-layouts-select"
					placeholder={getString('seat-types-select-placeholder')}
					options={seatTypes}
					onChange={(value) => console.log(value)}
					value={null}
				/>
			</LabeledItem>

			<a
				href={getLink('layouts')}
				target="_blank"
				className="button-link button-link--nested"
			>
				{getString('view-layouts-link-label')}
			</a>
		</Fragment>
	);
};
const MemoizedEventLayoutSelect = React.memo(EventLayoutSelect);

export default function CapacityForm({renderDefaultForm}) {
	const isUsingAssignedSeating = useSelect(
		(select) => {
			return select(storeName).isUsingAssignedSeating();
		},
		[],
	);
	const layouts = useSelect(
		(select) => {
			return select(storeName).getLayoutsInOptionFormat();
		},
		[],
	);
	const seatTypes = useSelect(
		(select) => {
			return select(storeName).getSeatTypesInOptionFormat();
		},
		[],
	);
	const {setUsingAssignedSeating} = useDispatch(store);
	const onChange = useCallback(() => {
		setUsingAssignedSeating(!isUsingAssignedSeating);
	}, [isUsingAssignedSeating, setUsingAssignedSeating]);

	return (
		<Fragment>
			<ToggleControl
				className="tec-events-assigned-seating__capacity-form__toggle"
				label={getString('use-assigned-seating-toggle-label')}
				checked={isUsingAssignedSeating}
				onChange={onChange}
			/>
			{isUsingAssignedSeating
				? <MemoizedEventLayoutSelect layouts={layouts}/>
				: renderDefaultForm()}
		</Fragment>
	);
}

CapacityForm.propTypes = {
	renderDefaultForm: PropTypes.func.isRequired,
};