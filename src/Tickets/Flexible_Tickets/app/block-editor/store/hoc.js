import { withSelect } from '@wordpress/data';

export const withStore = (mapStateToProps) =>
	withSelect((select) =>
		mapStateToProps(select('tec-tickets/flexible-tickets'))
	);
