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
import { selectors, actions } from '../../../../data/blocks/rsvp';
import { globals } from '@moderntribe/common/utils';

const mapStateToProps = ( state ) => ( {
	isDisabled: selectors.getRSVPIsLoading( state ),
	iac: selectors.getRSVPIAC( state ),
	iacOptions: globals.iacVars().iacOptions,
} );

const mapDispatchToProps = ( dispatch ) => ( {
	onChange: ( value ) => {
		dispatch( actions.setRSVPIAC( value ) );
		dispatch( actions.setRSVPHasChanges( true ) );
	},
} );

export default compose( withStore(), connect( mapStateToProps, mapDispatchToProps ) )( Template );
