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

const mapStateToProps = (state, ownProps) => {
	return {
		// mock data
		seriesName: 'My Series Test Name',
		seriesPassLink: 'https://theeventscalendar.com/',
	}
};

export default compose(withStore(), connect(mapStateToProps))(Template);
