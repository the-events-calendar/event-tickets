import {LabeledItem, Select} from '@moderntribe/common/elements';
import PropTypes from 'prop-types';
import {Fragment, useState} from 'react';
import {getLink, getLocalizedString} from '@tec/tickets/seating/utils';
import {Modal} from '@wordpress/components';

const getString = (key) => getLocalizedString(key, 'capacity-form');

const LayoutSelect = ({
	layouts,
	currentLayout
}) => {
	const getCurrentLayoutOption = (layoutId, layouts)=> {
		return layouts && layoutId
			? layouts.find((layoutOption) => layoutOption.value === layoutId)
			: null;
	}

	const [activeLayout, setActiveLayout] = useState(getCurrentLayoutOption(currentLayout, layouts));

	return (
		<div className="tec-tickets-seating__settings_layout--wrapper">
			<p className="tec-tickets-seating__settings_layout--title">Seat Layout</p>
			<Select
				id="tec-tickets-seating__settings_layout-select"
				value={activeLayout}
				options={layouts}
			/>
			<p>Changing the event's layout will impact all existing tickets and attendees.</p>
		</div>
	);
}

export default LayoutSelect;
