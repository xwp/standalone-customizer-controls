/* global module, jQuery, _, JSON, _standaloneCustomizerControlsExports */
/* exported StandaloneCustomizerControls */

var StandaloneCustomizerControls = (function( $ ) {
	'use strict';

	var component = {};

	component.data = {
		l10n: {}
	};
	if ( 'undefined' !== typeof _standaloneCustomizerControlsExports ) {
		_.extend( component.data, _standaloneCustomizerControlsExports );
	}

	/**
	 * Initialize.
	 *
	 * @param {object} api        The wp.customize object.
	 * @param {jQuery} containers Containers for the examples.
	 */
	component.init = function initializeStandaloneCustomizerControls( api, containers ) {
		component.api = api;

		// Create a mock previewer for any controls that look at it.
		if ( ! component.api.previewer ) {
			component.api.previewer = {
				deferred: {
					active: $.Deferred()
				}
			};
			component.api.previewer.deferred.active.resolve();
		}

		containers.each( function() {
			component.setupExample( $( this ) );
		} );
	};

	/**
	 * Embed standalone control.
	 *
	 * Overridden logic for `wp.customize.Control.prototype.embed` to embed straight
	 * away regardless of whether the control has a section or even an expanded
	 * section. See also `wp.customize.Menus.MenuItemControl.prototype.actuallyEmbed`
	 * for the same logic.
	 *
	 * @this {wp.customize.Control}
	 * @returns {void}
	 */
	component.embedControl = function embedControl() {
		var control = this;
		if ( 'resolved' !== control.deferred.embedded.state() ) {
			control.renderContent();
			control.deferred.embedded.resolve(); // This triggers control.ready().
		}
	};

	/**
	 * Set up an example.
	 */
	component.setupExample = function setupExample( container ) {
		var data, setting, SettingConstructor, control, ControlConstructor, settingTextarea, updateTextarea;
		data = container.data( 'config' );

		// Setting.
		SettingConstructor = component.api.settingConstructor[ data.setting.params.type ] || component.api.Setting;
		setting = new SettingConstructor(
			data.setting.id,
			data.setting.params.value,
			_.extend(
				{},
				data.setting.params,
				{
					previewer: null
				}
			)
		);
		component.api.add( setting.id, setting );

		// Add example client-side validation for settings being required.
		// @todo It would be preferred if this could be indicated declaratively on WP_Customize_Setting itself, and the JS logic added to wp.customize.Setting directly.
		setting.bind( function( value ) {
			var notification, code = 'required_value_invalidity';
			if ( _.isString( value ) && ! $.trim( value ) ) {
				if ( ! setting.notifications.has( code ) ) {
					notification = new component.api.Notification( code, {
						message: component.api.l10n[ code ] || code
					} );
					setting.notifications.add( notification.code, notification );
				}
			} else {
				setting.notifications.remove( code );
			}
		} );

		// Control.
		ControlConstructor = component.api.controlConstructor[ data.control.params.type ] || component.api.Control;
		ControlConstructor = ControlConstructor.extend( {
			embed: component.embedControl
		} );
		control = new ControlConstructor(
			data.control.id,
			{
				params: _.extend(
					{},
					data.control.params,
					{
						previewer: null,
						content: data.control.params.content || $( '<li></li>' )
					}
				)
			}
		);
		component.api.control.add( control.id, control );

		// Add the control to the DOM.
		container.find( 'fieldset.control > ul' ).append( control.container );

		// Set up a textarea to show and tweak the underlying setting value.
		settingTextarea = container.find( 'textarea.setting' );
		updateTextarea = function updateTextarea() {
			settingTextarea.val( JSON.stringify( setting.get() ) );
		};
		updateTextarea();
		setting.bind( updateTextarea );
		settingTextarea.on( 'input', function() {
			var parsed;
			try {
				parsed = JSON.parse( settingTextarea.val() );
				setting.unbind( updateTextarea );
				setting.set( parsed );
				setting.bind( updateTextarea );
				settingTextarea.removeClass( 'json-parse-error' );
			} catch ( e ) {
				settingTextarea.addClass( 'json-parse-error' );
			}
		} );
	};

	if ( 'undefined' !== typeof module ) {
		module.exports = component;
	}

	return component;

})( jQuery );
