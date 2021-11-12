/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since 5.0.3
 *
 * @type   {PlainObject}
 */
tribe.tickets = tribe.tickets || {};

/**
 * Configures ET Utils Object in the Global Tribe variable
 *
 * @since 5.0.3
 *
 * @type   {PlainObject}
 */
tribe.tickets.utils = {};

/**
 * Initializes in a Strict env the code that manages the plugin "utils".
 *
 * @since 5.0.3
 *
 * @param  {PlainObject} $   jQuery
 * @param  {PlainObject} obj tribe.tickets.utils
 *
 * @return {void}
 */
( function( $, obj ) {
	'use strict';
	const $document = $( document );

	/**
	 * Disable/Enable element.
	 *
	 * @since 5.0.3
	 *
	 * @param {object} $element jQuery object that we want to disable/enable.
	 * @param {boolean} isDisabled True if we want to disable the element.
	 *
	 * @return {void}
	 */
	obj.disable = function( $element, isDisabled ) {
		if ( isDisabled ) {
			$element.prop( 'disabled', true )
				.attr( {
					'disabled': 'true',
					'aria-disabled': 'true',
				} );
		} else {
			$element.prop( 'disabled', false )
				.removeProp( 'disabled' )
				.removeAttr( 'disabled aria-disabled' );
		}
	};

	/**
	 * Get the REST endpoint
	 *
	 * @since 5.0.3
	 *
	 * @returns {string} REST endpoint URL.
	 */
	obj.getRestEndpoint = function() {
		return TribeCartEndpoint.url;
	};

	/**
	 * Get the Currency Formatting for a Provider.
	 *
	 * @since 5.0.3
	 *
	 * @param {string} provider The provider.
	 *
	 * @returns {object} The appropriate currency format.
	 */
	obj.getCurrencyFormatting = function( provider ) {
		const currency = JSON.parse( TribeCurrency.formatting );

		return currency[ provider ];
	};

	/**
	 * Removes separator characters and converts decimal character to '.'
	 * So they play nice with other functions.
	 *
	 * @since TBD major refactoring of the internal logic.
	 * @since 5.0.3
	 *
	 * @todo clean up all calls to obj.cleanNumber to do not send a provider.
	 *
	 * @param {number} passedNumber The number to clean.
	 * @param {string} provider The provider.
	 *
	 * @returns {string} The cleaned number.
	 */
	obj.cleanNumber = function( passedNumber, provider ) { // eslint-disable-line no-unused-vars
		let nonDigits;
		let value;

		// If there are no decimals and no thousands separator we can return the number.
		nonDigits = passedNumber.match(/[^\d]/);

		if ( ! nonDigits ) {
			return passedNumber;
		}

		nonDigits = nonDigits.filter( function( value, index, self ) {
			return self.indexOf(value) === index;
		});

		for ( let i = 0; i < nonDigits.length; i++ ) {
			if ( this.isDecimalSeparator( nonDigits[i], passedNumber ) ) {
				value = passedNumber.replace( nonDigits[i], '.' );
				continue;
			}

			value = passedNumber.replaceAll( nonDigits[i], '' );
		}

		return value;
	};

	/**
	 * Determines if a given separator acts as a decimal separator or something else in a string.
	 *
	 * The rule to determine a decimal is straightforward. It needs to exist only once
	 * in the string and the piece of the string after the separator cannot be longer
	 * than 2 digits. Anything else is serving another purpose.
	 *
	 * @since TBD
	 *
	 * @param {string} separator a separator token, like . or ,
	 * @param {number} number    the number.
	 *
	 * @returns {boolean}
	 */
	obj.isDecimalSeparator = function( separator, number ) {
		const pieces = number.split( separator );

		if ( pieces && 2 === pieces.length ) {
			return pieces[1].length < 3;
		}

		return false;
	}

	/**
	 * Format the number according to provider settings.
	 * Based off coding from https://stackoverflow.com/a/2901136.
	 *
	 * @since 5.0.3
	 *
	 * @param {number} number The number to format.
	 * @param {string} provider The provider.
	 *
	 * @returns {string} The formatted number.
	 */
	obj.numberFormat = function( number, provider ) {
		const format = obj.getCurrencyFormatting( provider );

		if ( ! format ) {
			return false;
		}

		const decimals = format.number_of_decimals;
		const decPoint = format.decimal_point;
		const thousandsSep = format.thousands_sep;
		const n = ! isFinite( +number ) ? 0 : +number;
		const prec = ! isFinite( +decimals ) ? 0 : Math.abs( decimals );
		const sep = ( 'undefined' === typeof thousandsSep ) ? ',' : thousandsSep;
		const dec = ( 'undefined' === typeof decPoint ) ? '.' : decPoint;

		const toFixedFix = function( num, precision ) {
			// Fix for IE parseFloat(0.55).toFixed(0) = 0;
			const k = Math.pow( 10, precision );

			return Math.round( num * k ) / k;
		};

		let s = ( prec ? toFixedFix( n, prec ) : Math.round( n ) ).toString().split( '.' );

		if ( s[ 0 ].length > 3 ) {
			s[ 0 ] = s[ 0 ].replace( /\B(?=(?:\d{3})+(?!\d))/g, sep );
		}

		if ( ( s[ 1 ] || '' ).length < prec ) {
			s[ 1 ] = s[ 1 ] || '';
			s[ 1 ] += new Array( prec - s[ 1 ].length + 1 ).join( '0' );
		}

		return s.join( dec );
	};

	/**
	 * Get the tickets form, given a post ID.
	 *
	 * @since 5.0.3
	 *
	 * @param {number} postId The post id.
	 *
	 * @returns {jQuery} The jQuery object of the form.
	 */
	obj.getTicketsFormFromPostId = function( postId ) {
		return $document.find( tribe.tickets.block.selectors.form + '[data-post-id="' + postId + '"]' );
	};

	/**
	 * Get the tickets provider, given a post ID.
	 *
	 * @since 5.0.3
	 *
	 * @param {number} postId The post id.
	 *
	 * @returns {boolean|string} The provider, or false if it's not found.
	 */
	obj.getTicketsProviderFromPostId = function( postId ) {
		return obj.getTicketsFormFromPostId( postId ).data( 'provider' ) || false;
	};

	/**
	 * Get the tickets provider ID, given a post ID.
	 *
	 * @since 5.0.3
	 *
	 * @param {number} postId The post id.
	 *
	 * @returns {boolean|string} The provider ID, or false if it's not found.
	 */
	obj.getTicketsProviderIdFromPostId = function( postId ) {
		return obj.getTicketsFormFromPostId( postId ).data( 'provider-id' ) || false;
	};

	/**
	 * Get the first tickets block post ID
	 *
	 * @since 5.0.3
	 *
	 * @return {boolean|int} postId The post id.
	 */
	obj.getTicketsPostId = function() {
		const $ticketsBlock = $( tribe.tickets.block.selectors.form )[ 0 ];

		// Return the post id for the first ticket block.
		return $ticketsBlock.getAttribute( 'data-post-id' ) || false;
	};

	/**
	 * Get the price of the ticket from the ticket item element.
	 *
	 * @since TBD
	 *
	 * @return {float|int} The ticket price.
	 */
	obj.getPrice = function( $ticketItem, provider ) {
		if ( ! $ticketItem ) {
			return 0;
		}
		const realPrice = $ticketItem.data( 'ticket-price' );
		const formattedPrice = $ticketItem
			.find( '.tribe-tickets__tickets-sale-price .tribe-amount' )
			.text();
		const priceString = isNaN( realPrice )
			? obj.cleanNumber( formattedPrice, provider )
			: realPrice;
			return parseFloat( priceString );
	};

} )( jQuery, tribe.tickets.utils );
