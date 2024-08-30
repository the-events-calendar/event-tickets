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

	return (
		<div className="tec-tickets-seating__settings_layout-select">
			<h2>Seat Layout</h2>
		</div>
	);
}

export default LayoutSelect;
