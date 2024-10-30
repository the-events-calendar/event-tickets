import Template from './template';
import { withStore } from '../../store/hoc';

const mapStateToProps = (store) => {
	const { getSeriesInformation } = store;
	const { title, editLink } = getSeriesInformation();

	return {
		seriesName: title,
		seriesPassLink: editLink + '#tribetickets',
	};
};

export default withStore(mapStateToProps)(Template);
