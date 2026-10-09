/**
 * A stand-in for `@wordpress/data`, which is not installed: the block editor provides it at runtime.
 *
 * Each registered store runs its real reducer, actions and selectors in a Redux store, so specs read and write it
 * through `select()` and `dispatch()` as the block editor would.
 *
 * @since TBD
 */
const { createStore } = require( 'redux' );

const registered = {};

const createReduxStore = ( name, { reducer, actions, selectors } ) => ( { name, reducer, actions, selectors } );

const register = ( { name, reducer, actions, selectors } ) => {
	registered[ name ] = { store: createStore( reducer ), actions, selectors };
};

const select = ( name ) => {
	const { store, selectors } = registered[ name ];

	return Object.fromEntries(
		Object.entries( selectors ).map( ( [ key, selector ] ) => [
			key,
			( ...args ) => selector( store.getState(), ...args ),
		] )
	);
};

const dispatch = ( name ) => {
	const { store, actions } = registered[ name ];

	return Object.fromEntries(
		Object.entries( actions ).map( ( [ key, action ] ) => [
			key,
			( ...args ) => store.dispatch( action( ...args ) ),
		] )
	);
};

/**
 * Gets the whole state of a registered store, for specs that check what no selector exposes. Not part of
 * `@wordpress/data`.
 *
 * @param {string} name The store name.
 *
 * @return {Object} The store state.
 */
const getStoreState = ( name ) => registered[ name ].store.getState();

module.exports = { createReduxStore, register, select, dispatch, getStoreState };
