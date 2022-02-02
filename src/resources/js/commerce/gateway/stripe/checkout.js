/* global tribe, jQuery, Stripe, tecTicketsCommerceGatewayStripeCheckout */

/**
 * Minified by jsDelivr using Terser v5.3.5.
 * Original file: /npm/ky@0.27.0/index.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
/*! MIT License © Sindre Sorhus */
const isObject=t=>null!==t&&"object"==typeof t,supportsAbortController="function"==typeof globalThis.AbortController,supportsStreams="function"==typeof globalThis.ReadableStream,supportsFormData="function"==typeof globalThis.FormData,mergeHeaders=(t,e)=>{const s=new globalThis.Headers(t||{}),r=e instanceof globalThis.Headers,o=new globalThis.Headers(e||{});for(const[t,e]of o)r&&"undefined"===e||void 0===e?s.delete(t):s.set(t,e);return s},deepMerge=(...t)=>{let e={},s={};for(const r of t){if(Array.isArray(r))Array.isArray(e)||(e=[]),e=[...e,...r];else if(isObject(r)){for(let[t,s]of Object.entries(r))isObject(s)&&t in e&&(s=deepMerge(e[t],s)),e={...e,[t]:s};isObject(r.headers)&&(s=mergeHeaders(s,r.headers))}e.headers=s}return e},requestMethods=["get","post","put","patch","head","delete"],responseTypes={json:"application/json",text:"text/*",formData:"multipart/form-data",arrayBuffer:"*/*",blob:"*/*"},retryMethods=["get","put","head","delete","options","trace"],retryStatusCodes=[408,413,429,500,502,503,504],retryAfterStatusCodes=[413,429,503],stop=Symbol("stop");class HTTPError extends Error{constructor(t,e,s){super(t.statusText||String(0===t.status||t.status?t.status:"Unknown response error")),this.name="HTTPError",this.response=t,this.request=e,this.options=s}}class TimeoutError extends Error{constructor(t){super("Request timed out"),this.name="TimeoutError",this.request=t}}const delay=t=>new Promise((e=>setTimeout(e,t))),timeout=(t,e,s)=>new Promise(((r,o)=>{const i=setTimeout((()=>{e&&e.abort(),o(new TimeoutError(t))}),s.timeout);s.fetch(t).then(r).catch(o).then((()=>{clearTimeout(i)}))})),normalizeRequestMethod=t=>requestMethods.includes(t)?t.toUpperCase():t,defaultRetryOptions={limit:2,methods:retryMethods,statusCodes:retryStatusCodes,afterStatusCodes:retryAfterStatusCodes},normalizeRetryOptions=(t={})=>{if("number"==typeof t)return{...defaultRetryOptions,limit:t};if(t.methods&&!Array.isArray(t.methods))throw new Error("retry.methods must be an array");if(t.statusCodes&&!Array.isArray(t.statusCodes))throw new Error("retry.statusCodes must be an array");return{...defaultRetryOptions,...t,afterStatusCodes:retryAfterStatusCodes}},maxSafeTimeout=2147483647;class Ky{constructor(t,e={}){if(this._retryCount=0,this._input=t,this._options={credentials:this._input.credentials||"same-origin",...e,headers:mergeHeaders(this._input.headers,e.headers),hooks:deepMerge({beforeRequest:[],beforeRetry:[],afterResponse:[]},e.hooks),method:normalizeRequestMethod(e.method||this._input.method),prefixUrl:String(e.prefixUrl||""),retry:normalizeRetryOptions(e.retry),throwHttpErrors:!1!==e.throwHttpErrors,timeout:void 0===e.timeout?1e4:e.timeout,fetch:e.fetch||globalThis.fetch.bind(globalThis)},"string"!=typeof this._input&&!(this._input instanceof URL||this._input instanceof globalThis.Request))throw new TypeError("`input` must be a string, URL, or Request");if(this._options.prefixUrl&&"string"==typeof this._input){if(this._input.startsWith("/"))throw new Error("`input` must not begin with a slash when using `prefixUrl`");this._options.prefixUrl.endsWith("/")||(this._options.prefixUrl+="/"),this._input=this._options.prefixUrl+this._input}if(supportsAbortController&&(this.abortController=new globalThis.AbortController,this._options.signal&&this._options.signal.addEventListener("abort",(()=>{this.abortController.abort()})),this._options.signal=this.abortController.signal),this.request=new globalThis.Request(this._input,this._options),this._options.searchParams){const t="?"+("string"==typeof this._options.searchParams?this._options.searchParams.replace(/^\?/,""):new URLSearchParams(this._options.searchParams).toString()),e=this.request.url.replace(/(?:\?.*?)?(?=#|$)/,t);!(supportsFormData&&this._options.body instanceof globalThis.FormData||this._options.body instanceof URLSearchParams)||this._options.headers&&this._options.headers["content-type"]||this.request.headers.delete("content-type"),this.request=new globalThis.Request(new globalThis.Request(e,this.request),this._options)}void 0!==this._options.json&&(this._options.body=JSON.stringify(this._options.json),this.request.headers.set("content-type","application/json"),this.request=new globalThis.Request(this.request,{body:this._options.body}));const s=async()=>{if(this._options.timeout>2147483647)throw new RangeError("The `timeout` option cannot be greater than 2147483647");await delay(1);let t=await this._fetch();for(const e of this._options.hooks.afterResponse){const s=await e(this.request,this._options,this._decorateResponse(t.clone()));s instanceof globalThis.Response&&(t=s)}if(this._decorateResponse(t),!t.ok&&this._options.throwHttpErrors)throw new HTTPError(t,this.request,this._options);if(this._options.onDownloadProgress){if("function"!=typeof this._options.onDownloadProgress)throw new TypeError("The `onDownloadProgress` option must be a function");if(!supportsStreams)throw new Error("Streams are not supported in your environment. `ReadableStream` is missing.");return this._stream(t.clone(),this._options.onDownloadProgress)}return t},r=this._options.retry.methods.includes(this.request.method.toLowerCase())?this._retry(s):s();for(const[t,s]of Object.entries(responseTypes))r[t]=async()=>{this.request.headers.set("accept",this.request.headers.get("accept")||s);const o=(await r).clone();if("json"===t){if(204===o.status)return"";if(e.parseJson)return e.parseJson(await o.text())}return o[t]()};return r}_calculateRetryDelay(t){if(this._retryCount++,this._retryCount<this._options.retry.limit&&!(t instanceof TimeoutError)){if(t instanceof HTTPError){if(!this._options.retry.statusCodes.includes(t.response.status))return 0;const e=t.response.headers.get("Retry-After");if(e&&this._options.retry.afterStatusCodes.includes(t.response.status)){let t=Number(e);return Number.isNaN(t)?t=Date.parse(e)-Date.now():t*=1e3,void 0!==this._options.retry.maxRetryAfter&&t>this._options.retry.maxRetryAfter?0:t}if(413===t.response.status)return 0}return.3*2**(this._retryCount-1)*1e3}return 0}_decorateResponse(t){return this._options.parseJson&&(t.json=async()=>this._options.parseJson(await t.text())),t}async _retry(t){try{return await t()}catch(e){const s=Math.min(this._calculateRetryDelay(e),2147483647);if(0!==s&&this._retryCount>0){await delay(s);for(const t of this._options.hooks.beforeRetry){if(await t({request:this.request,options:this._options,error:e,retryCount:this._retryCount})===stop)return}return this._retry(t)}if(this._options.throwHttpErrors)throw e}}async _fetch(){for(const t of this._options.hooks.beforeRequest){const e=await t(this.request,this._options);if(e instanceof Request){this.request=e;break}if(e instanceof Response)return e}return!1===this._options.timeout?this._options.fetch(this.request.clone()):(t=this.request.clone(),e=this.abortController,s=this._options,new Promise(((r,o)=>{const i=setTimeout((()=>{e&&e.abort(),o(new TimeoutError(t))}),s.timeout);s.fetch(t).then(r).catch(o).then((()=>{clearTimeout(i)}))})));var t,e,s}_stream(t,e){const s=Number(t.headers.get("content-length"))||0;let r=0;return new globalThis.Response(new globalThis.ReadableStream({async start(o){const i=t.body.getReader();e&&e({percent:0,transferredBytes:0,totalBytes:s},new Uint8Array),await async function t(){const{done:n,value:a}=await i.read();if(n)o.close();else{if(e){r+=a.byteLength;e({percent:0===s?0:r/s,transferredBytes:r,totalBytes:s},a)}o.enqueue(a),await t()}}()}}))}}const validateAndMerge=(...t)=>{for(const e of t)if((!isObject(e)||Array.isArray(e))&&void 0!==e)throw new TypeError("The `options` argument must be an object");return deepMerge({},...t)},createInstance=t=>{const e=(e,s)=>new Ky(e,validateAndMerge(t,s));for(const s of requestMethods)e[s]=(e,r)=>new Ky(e,validateAndMerge(t,r,{method:s}));return e.HTTPError=HTTPError,e.TimeoutError=TimeoutError,e.create=t=>createInstance(validateAndMerge(t)),e.extend=e=>createInstance(validateAndMerge(t,e)),e.stop=stop,e},ky=createInstance();
//# sourceMappingURL=/sm/bf59b3ee4485b26143b77d9336e0e09c1be06821ed3eec513e4a10039d8ca259.map

/**
 * Path to this script in the global tribe Object.
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway.stripe = tribe.tickets.commerce.gateway.stripe || {};

/**
 * This script Object for public usage of the methods.
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway.stripe.checkout = {};

(( $, tc, Stripe, ky ) => {
	'use strict';

	/**
	 * The document element
	 *
	 * @since TBD
	 *
	 * @type {jQuery|HTMLElement}
	 */
	const $document = $( document );

	/**
	 * The gateway.stripe object from the global tribe object
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	const obj = tc.gateway.stripe;

	/**
	 * The billing object from the global tribe object
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	const billing = tc.billing;

	/**
	 * Pull the variables from the PHP backend.
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	obj.checkout = tecTicketsCommerceGatewayStripeCheckout;

	/**
	 * Checkout Selectors.
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	obj.selectors = {
		cardNumber: '#tec-tc-gateway-stripe-card-number',
		cardExpiry: '#tec-tc-gateway-stripe-card-expiry',
		cardCvc: '#tec-tc-gateway-stripe-card-cvc',
		cardZipWrapper: '#tec-tc-gateway-stripe-card-zip',
		cardElement: '#tec-tc-gateway-stripe-card-element',
		cardErrors: '#tec-tc-gateway-stripe-errors',
		paymentElement: '#tec-tc-gateway-stripe-payment-element',
		paymentMessage: '#tec-tc-gateway-stripe-payment-message',
		submitButton: '#tec-tc-gateway-stripe-checkout-button'
	};

	/**
	 * Stripe JS library.
	 *
	 * @since TBD
	 *
	 * @type {Object|null}
	 */
	obj.stripeLib = Stripe( obj.checkout.publishableKey );

	/**
	 * Handles the changing of the card field.
	 *
	 * @since TBD
	 *
	 * @param {Object} error Which error we are dealing with.
	 */
	obj.onCardChange = ( { error } ) => {
		let displayError = $( obj.selectors.cardErrors );
		if ( error ) {
			displayError.text( error.message );
		} else {
			displayError.text( '' );
		}
	};

	/**
	 * Toggle the submit button enabled/disabled
	 *
	 * @param enable
	 */
	obj.submitButton = ( enable ) => {
		let submitButton = document.querySelector( obj.selectors.submitButton );
		submitButton.disabled = ! enable;
	}

	/**
	 * Receive the Payment from Stripe.
	 *
	 * @since TBD
	 *
	 * @param {Object} result Result from the payment request.
	 *
	 * @return {boolean}
	 */
	obj.handleReceivePayment = async ( result ) => {
		tribe.tickets.debug.log( 'stripe', 'handleReceivePayment', result );
		if ( result.error ) {
			return obj.handlePaymentError( result );
		}

		if ( 'succeeded' === result.paymentIntent.status ) {
			return (await obj.handlePaymentSuccess( result ));
		}
	};

	/**
	 * When a successful request is completed to our Approval endpoint.
	 *
	 * @since TBD
	 *
	 * @param {Object} data Data returning from our endpoint.
	 *
	 * @return {boolean}
	 */
	obj.handlePaymentError = ( data ) => {
		console.log( data.error.message );

		var errorEl = document.querySelector( obj.selectors.cardErrors );
		errorEl.value = data.error.message;

		tribe.tickets.debug.log( 'stripe', 'handlePaymentError', data );
		return false;
	};

	/**
	 * When a successful request is completed to our Approval endpoint.
	 *
	 * @since TBD
	 *
	 * @param {Object} data Data returning from our endpoint.
	 *
	 * @return {boolean}
	 */
	obj.handlePaymentSuccess = async ( data ) => {
		tribe.tickets.debug.log( 'stripe', 'handlePaymentSuccess', data );

		const response = await obj.handleUpdateOrder( data.paymentIntent );

		// Redirect the user to the success page.
		window.location.replace( response.redirect_url );
		return true;
	};

	/**
	 * Updates the Order based on a paymentIntent from Stripe.
	 *
	 * @since TBD
	 *
	 * @param {Object} paymentIntent Payment intent Object from Stripe.
	 *
	 * @return {Promise<*>}
	 */
	obj.handleUpdateOrder = async ( paymentIntent ) => {
		const args = {
			json: {
				client_secret: paymentIntent.client_secret
			},
			headers: {
				'X-WP-Nonce': obj.checkout.nonce
			}
		};

		const response = await ky.post( `${obj.checkout.orderEndpoint}/${paymentIntent.id}`, args ).json();

		tribe.tickets.debug.log( 'stripe', 'updateOrder', response );

		return response;
	};

	/**
	 * Submit the payment to stripe code.
	 *
	 * @param {String} order The order object returned from the server.
	 *
	 * @return {Promise<*>}
	 */
	obj.submitMultiPayment = async ( order ) => {
		var elements = obj.stripeElements;
		var billing_details = billing.getDetails(false);
		var order = order;
		return obj.stripeLib.confirmPayment( {
			elements,
			redirect: 'if_required',
			confirmParams: {
				return_url: order.redirect_url,
				payment_method_data: {
					billing_details: billing_details
				}
			}
		} ).then( function( result ) {
			obj.submitButton(true);
			if ( result.error ) {
				obj.handlePaymentError( result );
			} else {
				if ( result.paymentIntent.status === 'succeeded' ) {
					obj.handlePaymentSuccess( result );
				}
			}
		} );
	};

	/**
	 * Submit the Card Element payment to stripe.
	 *
	 * @returns {Promise<void>}
	 */
	obj.submitCardPayment = async () => {
		var billing_details = billing.getDetails(false);

		obj.stripeLib.confirmCardPayment( obj.checkout.paymentIntentData.key, {
			payment_method: {
				card: obj.cardElement,
				billing_details: billing_details
			}
		} ).then( function( result ) {
			obj.submitButton(true);
			if ( result.error ) {
				obj.handlePaymentError( result );
			} else {
				if ( result.paymentIntent.status === 'succeeded' ) {
					obj.handlePaymentSuccess( result );
				}
			}
		} );
	};

	/**
	 * Create an order and start the payment process.
	 *
	 * @since TBD
	 *
	 * @return {Promise<*>}
	 */
	obj.handleCreateOrder = async () => {
		const args = {
			json: {
				billing_details: billing.getDetails(),
				payment_intent: obj.checkout.paymentIntentData
			},
			headers: {
				'X-WP-Nonce': obj.checkout.nonce
			}
		};
		// Fetch Publishable API Key and Initialize Stripe Elements on Ready
		let response = await ky.post( obj.checkout.orderEndpoint, args ).json();

		tribe.tickets.debug.log( 'stripe', 'createOrder', response );

		return response;
	};

	/**
	 * Starts the process to submit a payment.
	 *
	 * @since TBD
	 *
	 * @param {Event} event The Click event from the payment.
	 */
	obj.handlePayment = async ( event ) => {
		event.preventDefault();

		let order = await obj.handleCreateOrder();
		obj.submitButton(false);

		if ( order.success ) {
			if ( obj.checkout.paymentElement ) {
				obj.submitMultiPayment( order );
			} else {
				obj.submitCardPayment();
			}
		}
	};

	/**
	 * Setup and initialize Stripe API.
	 *
	 * @since TBD
	 *
	 * @return {Promise<void>}
	 */
	obj.setupStripe = async () => {
		obj.stripeElements = obj.stripeLib.elements( { clientSecret: obj.checkout.paymentIntentData.key } );

		if ( obj.checkout.paymentElement ) {
			// Instantiate the PaymentElement
			obj.paymentElement = obj.stripeElements.create( 'payment', {
				fields: {
					// We're collecting names and emails separately and sending them in confirmPayment
					// no need to duplicate it here
					name: 'never',
					email: 'never',
					phone: 'auto',
					address: 'auto'
				}
			} );
			obj.paymentElement.mount( obj.selectors.paymentElement );
			return false;
		}

		if ( obj.checkout.cardElementType !== 'compact' ) {
			// Instantiate the CardElement with individual fields
			obj.cardElement = obj.stripeElements.create( 'cardNumber', { showIcon: true, iconStyle: 'default' } );
			obj.cardElement.mount( obj.selectors.cardNumber );
			obj.cardExpiry = obj.stripeElements.create( 'cardExpiry' );
			obj.cardExpiry.mount( obj.selectors.cardExpiry );
			obj.cardCvc = obj.stripeElements.create( 'cardCvc' );
			obj.cardCvc.mount( obj.selectors.cardCvc );
			var cardZipWrapper = document.querySelector( obj.selectors.cardZipWrapper );
			var zipField = document.createElement( 'input' );
			cardZipWrapper.append(zipField);

			return false;
		}

		// Instantiate the CardElement with a single field combo
		obj.cardElement = obj.stripeElements.create( 'card' );
		obj.cardElement.mount( obj.selectors.cardElement );
		obj.cardElement.on( 'change', obj.onCardChange );
	};

	/**
	 * Bind script loader to trigger script dependent methods.
	 *
	 * @since TBD
	 */
	obj.bindEvents = () => {
		$( obj.selectors.submitButton ).on( 'click', obj.handlePayment );
	};

	/**
	 * When the page is ready.
	 *
	 * @since TBD
	 */
	obj.ready = () => {
		obj.setupStripe();
		obj.bindEvents();
	};

	$( obj.ready );
})( jQuery, tribe.tickets.commerce, Stripe, ky );
