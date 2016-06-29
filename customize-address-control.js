/* global wp */
/* eslint consistent-this: [ "error", "control" ] */

(function( api ) {
	'use strict';

	/**
	 * Address control.
	 *
	 * @class
	 * @augments wp.customize.Control
	 * @augments wp.customize.Class
	 */
	api.AddressControl = api.DynamicControl.extend({

		initialize: function( id, options ) {
			var control = this;
			api.DynamicControl.prototype.initialize.call( control, id, options );
		}
	});

	api.controlConstructor.address = api.AddressControl;

})( wp.customize );
