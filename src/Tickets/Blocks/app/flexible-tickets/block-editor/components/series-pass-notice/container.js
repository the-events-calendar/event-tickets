/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
import {
	getSeriesTitleFromSelection,
	getSeriesEditLinkFromMetaBox,
} from '../../../series-relationship';

const mapStateToProps = () => {
	return {
		seriesName: getSeriesTitleFromSelection(),
		seriesPassLink: getSeriesEditLinkFromMetaBox('#tribetickets'),
	};
};

export default compose(withStore(), connect(mapStateToProps))(Template);
