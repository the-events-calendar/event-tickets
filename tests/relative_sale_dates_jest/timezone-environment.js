/**
 * A Jest environment that runs a spec file in the timezone its `@timezone` docblock pragma names.
 *
 * Setting `process.env.TZ` inside a test changes nothing: Jest hands the test a copy of `process.env`, so the
 * timezone of the process running it never changes. This environment sets it on the process itself, for one file.
 *
 * @since TBD
 */
const JSDOMEnvironmentGlobal = require( 'jest-environment-jsdom-global' );

module.exports = class TimezoneEnvironment extends JSDOMEnvironmentGlobal {
	/**
	 * @param {Object} config  The Jest project configuration.
	 * @param {Object} context The test file context, with its docblock pragmas.
	 */
	constructor( config, context ) {
		super( config, context );
		this.timezone = context.docblockPragmas.timezone;
		this.originalTimezone = process.env.TZ;
	}

	async setup() {
		if ( this.timezone ) {
			process.env.TZ = this.timezone;
		}

		await super.setup();
	}

	async teardown() {
		await super.teardown();

		if ( undefined === this.originalTimezone ) {
			delete process.env.TZ;
		} else {
			process.env.TZ = this.originalTimezone;
		}
	}
};
