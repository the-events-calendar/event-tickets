const fs = require( 'fs' );
const path = require( 'path' );

/*
 * Tooltipster ships as a vendored UMD bundle in common, not as an npm package. Evaluating it with
 * `module` and `define` undefined takes its browser branch, which attaches it to the jQuery passed in.
 */
const loadTooltipster = () => {
	const bundle = fs.readFileSync(
		path.resolve( __dirname, '../../../../common/vendor/tooltipster/tooltipster.bundle.js' ),
		'utf8'
	);
	new Function( 'module', 'exports', 'define', 'jQuery', bundle )( undefined, undefined, undefined, jQuery ); // eslint-disable-line no-new-func
};

/*
 * The legacy RSVP block and the Tickets Commerce RSVP each ship their own copy of the tooltip
 * module, and both attach it to the same global, so each one is loaded in isolation.
 */
describe.each( [ [ 'v2/rsvp-tooltip' ], [ 'commerce/rsvp-tooltip' ] ] )( 'RSVP tooltip (%s)', ( modulePath ) => {
	const containerSelector = '.tribe-tickets__rsvp-wrapper';
	const triggerSelector = '[data-js="tribe-tickets-tooltip"]';
	const rsvpId = 2342;
	const contentId = `tribe-tickets-tooltip-content-${ rsvpId }`;
	let tooltip;

	const buildContainer = ( triggerContent ) => {
		const $container = jQuery( `
			<div class="tribe-tickets__rsvp-wrapper" data-rsvp-id="${ rsvpId }">
				<button data-js="tribe-tickets-tooltip" data-tooltip-content="">Remove me from the list</button>
				<div class="tribe-common-a11y-hidden">
					<div class="tribe-tickets-tooltip__content" id="${ contentId }" role="tooltip">Attendee list details</div>
				</div>
			</div>
		` );
		$container.find( triggerSelector ).attr( 'data-tooltip-content', triggerContent );
		jQuery( document.body ).append( $container );

		return $container;
	};

	beforeAll( () => {
		loadTooltipster();
		global.tribe = { tickets: { rsvp: { manager: { selectors: { container: containerSelector } } } } };
		jest.isolateModules( () => {
			require( `../${ modulePath }` );
		} );
		tooltip = global.tribe.tickets.rsvp.tooltip;
	} );

	afterAll( () => {
		delete global.tribe;
	} );

	afterEach( () => {
		jest.restoreAllMocks();
		document.body.innerHTML = '';
	} );

	test( 'shows the tooltip element the RSVP printed for the trigger', () => {
		const $container = buildContainer( `#${ contentId }` );
		const $trigger = $container.find( triggerSelector );

		tooltip.initTooltips( $container );

		expect( $trigger.tooltipster( 'content' ).get( 0 ) ).toBe( document.getElementById( contentId ) );
	} );

	test( 'never parses the trigger attribute as markup', () => {
		const markup = '<i data-probe="1"></i>';
		const parseHTML = jest.spyOn( jQuery, 'parseHTML' );
		const $container = buildContainer( markup );
		parseHTML.mockClear();

		tooltip.initTooltips( $container );

		expect( parseHTML ).not.toHaveBeenCalledWith( markup, expect.anything(), expect.anything() );
		expect( $container.find( triggerSelector ).hasClass( 'tooltipstered' ) ).toBe( false );
	} );

	test( 'ignores a trigger that points at an element outside its RSVP', () => {
		const outsideId = `${ contentId }-outside`;
		jQuery( document.body ).append( `<div id="${ outsideId }">Not printed by the RSVP</div>` );
		const $container = buildContainer( `#${ outsideId }` );

		tooltip.initTooltips( $container );

		expect( $container.find( triggerSelector ).hasClass( 'tooltipstered' ) ).toBe( false );
	} );

	test( 'tears down an RSVP whose triggers were skipped', () => {
		const $container = buildContainer( '<b>x</b>' );
		tooltip.initTooltips( $container );

		expect( () => tooltip.deinitTooltips( $container ) ).not.toThrow();
	} );
} );
