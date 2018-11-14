/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';
import includes from 'lodash/includes';

/**
 * Internal dependencies
 */
import EcommerceOptions from './template';
import { constants, selectors } from '@moderntribe/tickets/data/blocks/ticket';
import { withStore } from '@moderntribe/common/hoc';
import { globals } from '@moderntribe/common/utils';
const { config } = globals;

const { EDD, WOO } = constants;

const showEcommerceOptions = ( provider ) => includes( [ EDD, WOO ], provider );

const getEditTicketLink = ( state, ownProps, provider ) => {
	const adminURL = config().admin_url || '';
	const ticketId = selectors.getTicketId( state, ownProps );

	return showEcommerceOptions( provider ) ? `${ adminURL }post.php?post=${ ticketId }&action=edit` : '';
};

const getReportLink = ( state, ownProps, provider ) => {
	const adminURL = config().admin_url || '';
	const ticketId = selectors.getTicketId( state, ownProps );
	let path = '';

	if ( provider === EDD ) {
		path = `edit.php?page=edd-reports&view=sales&post_type=download&tab=logs&download=${ ticketId }`;
	} else if ( provider === WOO ) {
		path = `admin.php?page=wc-reports&tab=orders&report=sales_by_product&product_ids=${ ticketId }`;
	}

	return showEcommerceOptions( provider ) ? `${ adminURL }${ path }` : '';
};

const mapStateToProps = ( state, ownProps ) => {
	const provider = selectors.getTicketProvider( state, ownProps );

	return {
		provider,
		editTicketLink: getEditTicketLink( state, ownProps, provider ),
		reportLink: getReportLink( state, ownProps, provider ),
		showEcommerceOptions: showEcommerceOptions( provider ),
	};
};

export default compose(
	withStore(),
	connect( mapStateToProps ),
)( EcommerceOptions );
