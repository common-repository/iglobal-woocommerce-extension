<?php
  header('Content-Type: text/javascript');
  $store_id = $_GET['storeId'];
  $logo_url = (!empty($_GET['logoUrl'])) ? $_GET['logoUrl'] : "https://checkout.iglobalstores.com/images/demostore.png";
?>

// iGlobal Welcome Mat Script
// Authored by iGlobal Stores (www.iglobalstores.com)
// Copyright iGlobal Stores 2014

//
// Store specific settings
//

// Replace with your STORE ID
var ig_storeId = <?php echo $store_id ?>;
// Replace with your domain
var ig_cookieDomain = window.location.hostname;// If you prefer, you can put your domain here, like so "yourdomain.com";
// All countries, including the US
var ig_countries = {};
// List of country codes that are not considered international, default US, that the splash won't be shown to automatically
var ig_domesticCountryCodes = [];
// List of country codes you don't ship to
var ig_noShipCountryCodes = []// Logo URL, transparent background
var ig_logoUrl = "<?php echo $logo_url; ?>";

//
// Set internal JQuery Variable
// Can set to existing $ on page, or can include Jquery here, and set igJq to jquery-no-conflict
//

igJq = jQuery; //Sets internal jquery variable to the existing $ on the page.

//
// END Store specific settings
//

///////////////////////////////////////////////////////////////////////////////

//
// Begin jquery jsonp plugin
//
/*
 * jQuery JSONP Core Plugin 2.4.0 (2012-08-21)
 *
 * https://github.com/jaubourg/jquery-jsonp
 *
 * Copyright (c) 2012 Julian Aubourg
 *
 * This document is licensed as free software under the terms of the
 * MIT License: http://www.opensource.org/licenses/mit-license.php
 */
( function( igJq ) {

    // ###################### UTILITIES ##

    // Noop
    function noop() {
    }

    // Generic callback
    function genericCallback( data ) {
        lastValue = [ data ];
    }

    // Call if defined
    function callIfDefined( method , object , parameters ) {
        return method && method.apply( object.context || object , parameters );
    }

    // Give joining character given url
    function qMarkOrAmp( url ) {
        return /\?/ .test( url ) ? "&" : "?";
    }

    var // String constants (for better minification)
        STR_ASYNC = "async",
        STR_CHARSET = "charset",
        STR_EMPTY = "",
        STR_ERROR = "error",
        STR_INSERT_BEFORE = "insertBefore",
        STR_JQUERY_JSONP = "_jqjsp",
        STR_ON = "on",
        STR_ON_CLICK = STR_ON + "click",
        STR_ON_ERROR = STR_ON + STR_ERROR,
        STR_ON_LOAD = STR_ON + "load",
        STR_ON_READY_STATE_CHANGE = STR_ON + "readystatechange",
        STR_READY_STATE = "readyState",
        STR_REMOVE_CHILD = "removeChild",
        STR_SCRIPT_TAG = "<script>",
        STR_SUCCESS = "success",
        STR_TIMEOUT = "timeout",

    // Window
        win = window,
    // Deferred
        Deferred = igJq.Deferred,
    // Head element
        head = igJq( "head" )[ 0 ] || document.documentElement,
    // Page cache
        pageCache = {},
    // Counter
        count = 0,
    // Last returned value
        lastValue,

    // ###################### DEFAULT OPTIONS ##
        xOptionsDefaults = {
            //beforeSend: undefined,
            //cache: false,
            callback: STR_JQUERY_JSONP,
            //callbackParameter: undefined,
            //charset: undefined,
            //complete: undefined,
            //context: undefined,
            //data: "",
            //dataFilter: undefined,
            //error: undefined,
            //pageCache: false,
            //success: undefined,
            //timeout: 0,
            //traditional: false,
            url: location.href
        },

    // opera demands sniffing :/
        opera = win.opera,

    // IE < 10
        oldIE = !!igJq( "<div>" ).html( "<!--[if IE]><i><![endif]-->" ).find("i").length;

    // ###################### MAIN FUNCTION ##
    function jsonp( xOptions ) {

        // Build data with default
        xOptions = igJq.extend( {} , xOptionsDefaults , xOptions );

        // References to xOptions members (for better minification)
        var successCallback = xOptions.success,
            errorCallback = xOptions.error,
            completeCallback = xOptions.complete,
            dataFilter = xOptions.dataFilter,
            callbackParameter = xOptions.callbackParameter,
            successCallbackName = xOptions.callback,
            cacheFlag = xOptions.cache,
            pageCacheFlag = xOptions.pageCache,
            charset = xOptions.charset,
            url = xOptions.url,
            data = xOptions.data,
            timeout = xOptions.timeout,
            pageCached,

        // Abort/done flag
            done = 0,

        // Life-cycle functions
            cleanUp = noop,

        // Support vars
            supportOnload,
            supportOnreadystatechange,

        // Request execution vars
            firstChild,
            script,
            scriptAfter,
            timeoutTimer;

        // If we have Deferreds:
        // - substitute callbacks
        // - promote xOptions to a promise
        Deferred && Deferred(function( defer ) {
            defer.done( successCallback ).fail( errorCallback );
            successCallback = defer.resolve;
            errorCallback = defer.reject;
        }).promise( xOptions );

        // Create the abort method
        xOptions.abort = function() {
            !( done++ ) && cleanUp();
        };

        // Call beforeSend if provided (early abort if false returned)
        if ( callIfDefined( xOptions.beforeSend , xOptions , [ xOptions ] ) === !1 || done ) {
            return xOptions;
        }

        // Control entries
        url = url || STR_EMPTY;
        data = data ? ( (typeof data) == "string" ? data : igJq.param( data , xOptions.traditional ) ) : STR_EMPTY;

        // Build final url
        url += data ? ( qMarkOrAmp( url ) + data ) : STR_EMPTY;

        // Add callback parameter if provided as option
        callbackParameter && ( url += qMarkOrAmp( url ) + encodeURIComponent( callbackParameter ) + "=?" );

        // Add anticache parameter if needed
        !cacheFlag && !pageCacheFlag && ( url += qMarkOrAmp( url ) + "_" + ( new Date() ).getTime() + "=" );

        // Replace last ? by callback parameter
        url = url.replace( /=\?(&|$)/ , "=" + successCallbackName + "$1" );

        // Success notifier
        function notifySuccess( json ) {

            if ( !( done++ ) ) {

                cleanUp();
                // Pagecache if needed
                pageCacheFlag && ( pageCache [ url ] = { s: [ json ] } );
                // Apply the data filter if provided
                dataFilter && ( json = dataFilter.apply( xOptions , [ json ] ) );
                // Call success then complete
                callIfDefined( successCallback , xOptions , [ json , STR_SUCCESS, xOptions ] );
                callIfDefined( completeCallback , xOptions , [ xOptions , STR_SUCCESS ] );

            }
        }

        // Error notifier
        function notifyError( type ) {

            if ( !( done++ ) ) {

                // Clean up
                cleanUp();
                // If pure error (not timeout), cache if needed
                pageCacheFlag && type != STR_TIMEOUT && ( pageCache[ url ] = type );
                // Call error then complete
                callIfDefined( errorCallback , xOptions , [ xOptions , type ] );
                callIfDefined( completeCallback , xOptions , [ xOptions , type ] );

            }
        }

        // Check page cache
        if ( pageCacheFlag && ( pageCached = pageCache[ url ] ) ) {

            pageCached.s ? notifySuccess( pageCached.s[ 0 ] ) : notifyError( pageCached );

        } else {

            // Install the generic callback
            // (BEWARE: global namespace pollution ahoy)
            win[ successCallbackName ] = genericCallback;

            // Create the script tag
            script = igJq( STR_SCRIPT_TAG )[ 0 ];
            script.id = STR_JQUERY_JSONP + count++;

            // Set charset if provided
            if ( charset ) {
                script[ STR_CHARSET ] = charset;
            }

            opera && opera.version() < 11.60 ?
                // onerror is not supported: do not set as async and assume in-order execution.
                // Add a trailing script to emulate the event
                ( ( scriptAfter = igJq( STR_SCRIPT_TAG )[ 0 ] ).text = "document.getElementById('" + script.id + "')." + STR_ON_ERROR + "()" )
                :
                // onerror is supported: set the script as async to avoid requests blocking each others
                ( script[ STR_ASYNC ] = STR_ASYNC )

            ;

            // Internet Explorer: event/htmlFor trick
            if ( oldIE ) {
                script.htmlFor = script.id;
                script.event = STR_ON_CLICK;
            }

            // Attached event handlers
            script[ STR_ON_LOAD ] = script[ STR_ON_ERROR ] = script[ STR_ON_READY_STATE_CHANGE ] = function ( result ) {

                // Test readyState if it exists
                if ( !script[ STR_READY_STATE ] || !/i/.test( script[ STR_READY_STATE ] ) ) {

                    try {

                        script[ STR_ON_CLICK ] && script[ STR_ON_CLICK ]();

                    } catch( _ ) {}

                    result = lastValue;
                    lastValue = 0;
                    result ? notifySuccess( result[ 0 ] ) : notifyError( STR_ERROR );

                }
            };

            // Set source
            script.src = url;

            // Re-declare cleanUp function
            cleanUp = function( i ) {
                timeoutTimer && clearTimeout( timeoutTimer );
                script[ STR_ON_READY_STATE_CHANGE ] = script[ STR_ON_LOAD ] = script[ STR_ON_ERROR ] = null;
                head[ STR_REMOVE_CHILD ]( script );
                scriptAfter && head[ STR_REMOVE_CHILD ]( scriptAfter );
            };

            // Append main script
            head[ STR_INSERT_BEFORE ]( script , ( firstChild = head.firstChild ) );

            // Append trailing script if needed
            scriptAfter && head[ STR_INSERT_BEFORE ]( scriptAfter , firstChild );

            // If a timeout is needed, install it
            timeoutTimer = timeout > 0 && setTimeout( function() {
                notifyError( STR_TIMEOUT );
            } , timeout );

        }

        return xOptions;
    }

    // ###################### SETUP FUNCTION ##
    jsonp.setup = function( xOptions ) {
        igJq.extend( xOptionsDefaults , xOptions );
    };

    // ###################### INSTALL in jQuery ##
    igJq.jsonp = jsonp;

} )( igJq );
//
// End jquery jsonp plugin
//

//
// Begin embedded easyModal.js
//
/**
 * easyModal.js v1.1.0
 * A minimal jQuery modal that works with your CSS.
 * Author: Flavius Matis - http://flaviusmatis.github.com/
 * URL: https://github.com/flaviusmatis/easyModal.js
 */

(function(igJq){

    var methods = {
        init : function(options) {

            var defaults = {
                top: '100',
                autoOpen: false,
                overlayOpacity: 0.5,
                overlayColor: '#000',
                overlayClose: true,
                overlayParent: 'body',
                closeOnEscape: true,
                closeButtonClass: '.close',
                onOpen: false,
                onClose: false
            };

            options = igJq.extend(defaults, options);

            return this.each(function() {

                var o = options;

                var $overlay = igJq('<div class="lean-overlay"></div>');

                $overlay.css({
                    'display': 'none',
                    'position': 'fixed',
                    'z-index': 2000,
                    'top': 0,
                    'left': 0,
                    'height': 100 + '%',
                    'width': 100+ '%',
                    'background': o.overlayColor,
                    'opacity': o.overlayOpacity
                }).appendTo(o.overlayParent);

                var $modal = igJq(this);

                $modal.css({
                    'display': 'none',
                    'position' : 'absolute',
                    'z-index': 2001,
                    'left' : 50 + '%',
                    'top' : parseInt(o.top) > -1 ? o.top + 'px' : 50 + '%'
                });

                $modal.bind('openModal', function(){
                    igJq(this).css({
                        'display' : 'block',
                        'margin-left' : -($modal.outerWidth()/2) + 'px',
                        'margin-top' : (parseInt(o.top) > -1 ? 0 : -($modal.outerHeight()/2)) + 'px'
                    });
                    $overlay.fadeIn(200, function(){
                        if (o.onOpen && typeof (o.onOpen) === 'function') {
                            // onOpen callback receives as argument the modal window
                            o.onOpen($modal[0]);
                        }
                    });
                });

                $modal.bind('closeModal', function(){
                    igJq(this).css('display', 'none');
                    $overlay.fadeOut(200, function(){
                        if (o.onClose && typeof(o.onClose) === 'function') {
                            // onClose callback receives as argument the modal window
                            o.onClose($modal[0]);
                        }
                    });
                });

                // Close on overlay click
                $overlay.click(function() {
                    if (o.overlayClose)
                        $modal.trigger('closeModal');
                });

                igJq(document).keydown(function(e) {
                    // ESCAPE key pressed
                    if (o.closeOnEscape && e.keyCode == 27) {
                        $modal.trigger('closeModal');
                    }
                });

                // Close when button pressed
                $modal.on('click', o.closeButtonClass, function(e) {
                    $modal.trigger('closeModal');
                    e.preventDefault();
                });

                // Automatically open modal if option set
                if (o.autoOpen)
                    $modal.trigger('openModal');

            });

        }
    };

    igJq.fn.easyModal = function(method) {

        // Method calling logic
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || ! method) {
            return methods.init.apply(this, arguments);
        } else {
            igJq.error('Method ' + method + ' does not exist on jQuery.easyModal');
        }

    };

})(igJq);

/*!
 * jQuery Cookie Plugin v1.4.1
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2006, 2014 Klaus Hartl
 * Released under the MIT license
 */
(function (factory) {
	if (typeof define === 'function' && define.amd) {
		// AMD (Register as an anonymous module)
		define(['igJq'], factory);
	} else if (typeof exports === 'object') {
		// Node/CommonJS
		module.exports = factory(require('igJq'));
	} else {
		// Browser globals
		factory(igJq);
	}
}(function (igJq) {

	var pluses = /\+/g;

	function encode(s) {
		return config.raw ? s : encodeURIComponent(s);
	}

	function decode(s) {
		return config.raw ? s : decodeURIComponent(s);
	}

	function stringifyCookieValue(value) {
		return encode(config.json ? JSON.stringify(value) : String(value));
	}

	function parseCookieValue(s) {
		if (s.indexOf('"') === 0) {
			// This is a quoted cookie as according to RFC2068, unescape...
			s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
		}

		try {
			// Replace server-side written pluses with spaces.
			// If we can't decode the cookie, ignore it, it's unusable.
			// If we can't parse the cookie, ignore it, it's unusable.
			s = decodeURIComponent(s.replace(pluses, ' '));
			return config.json ? JSON.parse(s) : s;
		} catch(e) {}
	}

	function read(s, converter) {
		var value = config.raw ? s : parseCookieValue(s);
		return igJq.isFunction(converter) ? converter(value) : value;
	}

	var config = igJq.cookie = function (key, value, options) {

		// Write

		if (arguments.length > 1 && !igJq.isFunction(value)) {
			options = igJq.extend({}, config.defaults, options);

			if (typeof options.expires === 'number') {
				var days = options.expires, t = options.expires = new Date();
				t.setMilliseconds(t.getMilliseconds() + days * 864e+5);
			}

			return (document.cookie = [
				encode(key), '=', stringifyCookieValue(value),
				options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
				options.path    ? '; path=' + options.path : '',
				options.domain  ? '; domain=' + options.domain : '',
				options.secure  ? '; secure' : ''
			].join(''));
		}

		// Read

		var result = key ? undefined : {},
			// To prevent the for loop in the first place assign an empty array
			// in case there are no cookies at all. Also prevents odd result when
			// calling $.cookie().
			cookies = document.cookie ? document.cookie.split('; ') : [],
			i = 0,
			l = cookies.length;

		for (; i < l; i++) {
			var parts = cookies[i].split('='),
				name = decode(parts.shift()),
				cookie = parts.join('=');

			if (key === name) {
				// If second argument (value) is a function it's a converter...
				result = read(cookie, value);
				break;
			}

			// Prevent storing a cookie that we couldn't decode.
			if (!key && (cookie = read(cookie)) !== undefined) {
				result[name] = cookie;
			}
		}

		return result;
	};

	config.defaults = {};

	igJq.removeCookie = function (key, options) {
		// Must not alter options, thus extending a fresh object...
		igJq.cookie(key, '', $.extend({}, options, { expires: -1 }));
		return !igJq.cookie(key);
	};

}));


//
// Begin iGlobal Stores Splash code
//

function ig_getParameterByName(name) {
    name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}

function ig_createSplashHtml() {
    var ig_splashHtml = '<div id="igSplashElement" style="display:none;">';
    ig_splashHtml += ig_createSplashContentsHtml();
    ig_splashHtml += '</div><!--/#igSplashElement -->';
    return ig_splashHtml;
}

function ig_createSplashContentsHtml() { // Feel free to edit the HTML below to match your site
    var ig_splashHtml = '' +
        '<img src="https://d1vyngmisxigjx.cloudfront.net/images/close-square.png" alt="Close" class="closeButton close" />' +
        '<div class="igModalHeader">' +
        '<div class="logoWrapper">' +
        '<img class="modalLogo" src="'+ig_logoUrl+'" alt="" />' +
        '</div><!--/.logoWrapper -->' +
        '<div class="messageWrapper">' +
        '<p class="headerZero">Thanks for visiting us from</p>' +
        '<img src="https://d1vyngmisxigjx.cloudfront.net/images/flags/96x64/'+((ig_country)?ig_country.toUpperCase():'undefined')+'.png" alt="Flag of '+ig_countries[ig_country]+'" class="headerFlag">' +
        '<span class="headerOne">' +ig_countries[ig_country]+'</span><!--/.headerOne-->' +
        '<p class="countryP" style="font-size: 12px;">Not in '+ig_countries[ig_country]+'? Please select your country below.</p> ' +
        '<div class="countryDropDownWrapper">' +
        '<select id="countrySelect" class=".coreUISelect" onchange="ig_countrySelected();"> ' +
        '<option value="" disabled>Select your country</option>';

    for(var countryCode in ig_countries){
        ig_splashHtml += '<option '+((countryCode===ig_country)?'selected="selected" ':'')+'value="'+countryCode+'">'+ig_countries[countryCode]+'</option>';
    }

    ig_splashHtml += '' +
        '</select>' +
        '</div><!--/.countryDropDownWrapper -->' +
        '</div><!--/.messageWrapper -->' +
        '</div><!--/.igModalHeader -->' +
        '<div class="igModalBody">';

    if (ig_isNoShipCountry()) {        //Don't show international shopping features, no-ship messaging
        ig_splashHtml += '' +
            '<ul class="featureList">' +
            '<div class="igFeatureHeader">We are sorry. We do not ship to your country at this time!</div>' +
            '</ul>';
    } else if (ig_isDomesticCountry()) {
        //Don't show international shopping features
        ig_splashHtml += '' +
            '<ul class="featureList">' +
            '<div class="igFeatureHeader">Welcome to our website!</div>' +
            '</ul>';
    } else {
        //Show international shopping features
        ig_splashHtml += ''+
            '<ul class="featureList">' +
            '<div class="igFeatureHeader">We offer the following services to shoppers in ' +ig_countries[ig_country]+'.</div>' +
            '<li><img src="https://d1vyngmisxigjx.cloudfront.net/images/currencies-icon.png" alt="Your Currency"> See totals and pay in <strong>your currency</strong>' +
            '</li>' +
            '<li><img src="https://d1vyngmisxigjx.cloudfront.net/images/payment-methods-icon.png" alt="Multiple Payment Methods"> <strong>Multiple payment methods</strong> available' +
            '</li>' +
            '<li><img src="https://d1vyngmisxigjx.cloudfront.net/images/prepay-duty-tax-icon.png" alt="Prepay Duties and Taxes"> Option to <strong>prepay duties and taxes</strong>' +
            '</li>' +
            '<li><img src="https://d1vyngmisxigjx.cloudfront.net/images/shipping-icon.png" alt="Shipping Options"> <strong>Multiple shipping options</strong> available' +
            '</li>' +
            '</ul>';
    }

    ig_splashHtml += '' +
        '<div class="igWelcomeCTAButton">' +
        '<button class="close">Start Shopping</button>' +
        '</div><!--/.igWelcomeCTAButton -->' +
        '</div><!--/.igModalBody -->' +
        '<div class="igModalFooter">' +
        '</div><!--/.igModalFooter-->';

    return ig_splashHtml;
}

function ig_countrySelected() {
    var countryCode = igJq("select#countrySelect").val();
    ig_setCountry(ig_validateCountryCode(countryCode));
    igJq("#igSplashElement").html(ig_createSplashContentsHtml());
    ig_alertIceOfCountryChange();
    set_buttons();
}

//Called by auto popup logic for first time non domestic country customers.  Also called by ALL customers clicking the nested flag on the page
function ig_showTheSplash() {
    //Construct the modal
    igJq("body").append(ig_createSplashHtml());

    //init easyModal.js modal, after modal content was placed on the page (line above)
    igJq("#igSplashElement").easyModal({
        onClose: function(myModal){
            //on close, let's remove the modal contents and the modal smokescreen created by easyModal.js
            igJq("#igSplashElement").remove();
            igJq(".lean-overlay").remove();
        }
    });

    //Fire the modal!
    igJq("#igSplashElement").trigger('openModal');

    //Set cookie for Splash shown
    if (ig_validateCountryCode(igJq.cookie("igCountry"))) { // Only set the splashShown cookie, if there is a valid countryCookie
        igJq.cookie('igSplash', 'igSplash');
    }
}

function ig_createNestContents() {
    return '<img onclick="ig_showTheSplash();" src="https://d1vyngmisxigjx.cloudfront.net/images/flags/96x64/'+((ig_country)?ig_country.toUpperCase():'undefined')+'.png" class="igWelcomeFlagHeader" alt="Select your country." />';
}

function ig_placeNestHtml() {
    igJq(function(){
        if (igJq("#igFlag")) {
            igJq("#igFlag").html(ig_createNestContents());
        }
    });
}

function ig_setCountry(country) {
    ig_country = country;
    if (ig_country) {
        //Set country cookie
        console.log("set country", ig_country);
        igJq.cookie('igCountry', ig_country, {expires: 7, path: '/'});
        console.log("Country Set");
    }
    ig_placeNestHtml();
}

function ig_alertIceOfCountryChange() {
    try {
        ig_ice_countryChanged(); // let the ICE script know that the country has changed, if there is an ICE script
    } catch (err) {
        //do nothing
    }
}

function ig_validateCountryCode(countryCode) {
    //Return the country code if valid, return null if not valid
    var countryDisplayName = ig_countries[countryCode];
    if (typeof countryDisplayName !== 'undefined' && countryDisplayName) {
        return countryCode;
    } else {
        return null;
    }
}

function ig_isNoShipCountry() {
    if(ig_countries[ig_country]) {       return ig_country && igJq.inArray(ig_country, ig_noShipCountryCodes) >= 0;
    } else {
       return false;
    }
}

function ig_isDomesticCountry() {
    if(ig_countries[ig_country]) {       return ig_country && igJq.inArray(ig_country, ig_domesticCountryCodes) >= 0;
    } else {
       return true;
    }
}

function ig_detectCountryCallback(countryCode) {
    ig_setCountry(ig_validateCountryCode(countryCode));
    ig_finishLoading();
}

function ig_detectCountryCallbackError() { // Error handling method for when the jsonp call to get the countryCode fails, if it will get called?
    console.log("Couldn't detect country");
    //if we couldn't detect country, default to US
    ig_country = 'US';
    ig_finishLoading();
}

function ig_detectCountry() {
    igJq.jsonp({
        url: 'https://iprecon.iglobalstores.com/iGlobalIp.js?p=igcCallback',
        callback:'igcCallback',
        success: function(json, textStatus, xOptions){ig_detectCountryCallback(json);},
        error: function(){ig_detectCountryCallbackError();}
    });
}

function ig_pingIglobal() {
    try { // Don't break if this doesn't work
        if (!ig_countryParam) {//Only ping iGlobal for real visitors, not url parameter testing
            igJq.ajax({//we do not need to trap errors like 503's, for this call
                dataType: "jsonp",
                url: 'https://iprecon.iglobalstores.com/ping.js?s='+ig_storeId+'&c='+((ig_country)?ig_country:'')
            });
        }
    } catch (err) {
        // do nothing
    }
}

//function ig_errorPingIglobal() {
//    console.log("Couldn't update iGlobal");
//}

function ig_finishLoading() {
    ig_placeNestHtml();
    if (!ig_isDomesticCountry() && (!ig_splashCookie || !ig_country || ig_countryParam)) {
        igJq(ig_showTheSplash); //Schedule Showing the Splash
    }
    ig_alertIceOfCountryChange();
    ig_pingIglobal();
}


var ig_country = null;
var ig_splashCookie = igJq.cookie("igSplash");
