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

		component.api.control.bind( 'add', component.addControlInputValidation );
		component.extendSettingsWithValidityState();

		// Create a mock previewer for any controls that look at it.
		if ( ! component.api.previewer ) {
			component.api.previewer = {
				deferred: {
					active: $.Deferred()
				},
				send: function() {}
			};
			component.api.previewer.deferred.active.resolve();
		}

		containers.each( function() {
			component.setupExample( $( this ) );
		} );
	};

	/**
	 * Add integration with HTML5 validation.
	 *
	 * @todo Something like this would be good to have in Core.
	 *
	 * @param {wp.customize.Control} control Control.
	 */
	component.addControlInputValidation = function addControlInputValidation( control ) {
		var api = component.api;
		control.deferred.embedded.done( function() {
			control.setting.bind( function() {
				var invalidCount = 0, inactiveInputs = [], activeInvalidInput, notification, code = 'invalid_value';
				control.container.find( ':input' ).each( function() {
					var input = $( this );
					if ( ! input[0].checkValidity() ) {
						input.addClass( 'invalid' );
						invalidCount += 1;
						inactiveInputs.push( input );
					} else {
						input.removeClass( 'invalid' );
					}
				} );

				if ( 0 === invalidCount ) {
					control.setting.notifications.remove( code );
				} else {
					notification = new api.Notification( code, {
						message: api.l10n.validityErrors.invalid
					} );
					control.setting.notifications.add( code, notification );

					// Report validity on the focused invalid input, or on the first invalid input.
					activeInvalidInput = _.find( inactiveInputs, function( input ) {
						return input.is( document.activeElement );
					} );
					if ( ! activeInvalidInput ) {
						activeInvalidInput = inactiveInputs[0];
					}
					if ( activeInvalidInput[0].reportValidity ) {
						activeInvalidInput[0].reportValidity();
					}
				}
			} );
		} );
	};

	/**
	 * Extend settings with validity state.
	 *
	 * Watch the notifications on a setting with a given type of 'error' and set
	 * the validity state to true/false accordingly.
	 */
	component.extendSettingsWithValidityState = function extendSettingsWithValidityState() {
		var originalSettingInitialize = component.api.Setting.prototype.initialize;
		component.api.Setting.prototype.initialize = function( id, value, args ) {
			var watchNotifications, validity, notifications;
			originalSettingInitialize.call( this, id, value, args );
			validity = new component.api.Value( true );
			notifications = this.notifications;

			// Note that debounce is needed because the remove event is triggered _before_ the notification is removed.
			watchNotifications = _.debounce( function() {
				var errorCount = 0;
				notifications.each( function( notification ) {
					if ( 'error' === notification.type ) {
						errorCount += 1;
					}
				} );
				validity.set( 0 === errorCount );
			} );
			notifications.bind( 'add', watchNotifications );
			notifications.bind( 'remove', watchNotifications );
			this.validity = validity;
		};
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
		var data, setting, SettingConstructor, fieldset, control, ControlConstructor, controlExtensions, settingTextarea, updateTextarea, originalInitFrame;
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

		// Control.
		ControlConstructor = component.api.controlConstructor[ data.control.params.type ] || component.api.Control;
		controlExtensions = {
			embed: component.embedControl
		};
		if ( 'media' === data.control.params.type ) {
			originalInitFrame = ControlConstructor.prototype.initFrame;

			/**
			 * Initialize the media frame and preselect
			 *
			 * @todo The wp.customize.MediaControl should do this in core.
			 *
			 * @return {void}
			 */
			controlExtensions.initFrame = function initFrameAndSetInitialSelection() {
				var control = this;
				originalInitFrame.call( control );
				control.frame.on( 'open', function() {
					var selection = control.frame.state().get( 'selection' );
					if ( control.params.attachment && control.params.attachment.id ) {

						// @todo This should also pre-check the images in the media library grid.
						selection.reset( [ control.params.attachment ] );
					} else {
						selection.reset( [] );
					}
				} );
			};

			/**
			 * Patch MediaControl.prototype.ready to work without section.
			 *
			 * @todo Note that this is a patched version of what is in core to handle.
			 */
			controlExtensions.ready = function mediaControlReady() {
				var control = this, api = component.api;

				// Shortcut so that we don't have to use _.bind every time we add a callback.
				_.bindAll( control, 'restoreDefault', 'removeFile', 'openFrame', 'select', 'pausePlayer' );

				// Bind events, with delegation to facilitate re-rendering.
				control.container.on( 'click keydown', '.upload-button', control.openFrame );
				control.container.on( 'click keydown', '.upload-button', control.pausePlayer );
				control.container.on( 'click keydown', '.thumbnail-image img', control.openFrame );
				control.container.on( 'click keydown', '.default-button', control.restoreDefault );
				control.container.on( 'click keydown', '.remove-button', control.pausePlayer );
				control.container.on( 'click keydown', '.remove-button', control.removeFile );
				control.container.on( 'click keydown', '.remove-button', control.cleanupPlayer );

				// Resize the player controls when it becomes visible (ie when section is expanded).
				// @todo Add the conditionals for this to Core.
				if ( control.section() ) {
					api.section( control.section(), function( section ) {
						section.container
							.on( 'expanded', function() {
								if ( control.player ) {
									control.player.setControlsSize();
								}
							} )
							.on( 'collapsed', function() {
								control.pausePlayer();
							} );
					} );
				} else {
					if ( control.player ) {
						control.player.setControlsSize();
					}
				}

				/**
				 * Set attachment data and render content.
				 *
				 * Note that BackgroundImage.prototype.ready applies this ready method
				 * to itself. Since BackgroundImage is an UploadControl, the value
				 * is the attachment URL instead of the attachment ID. In this case
				 * we skip fetching the attachment data because we have no ID available,
				 * and it is the responsibility of the UploadControl to set the control's
				 * attachmentData before calling the renderContent method.
				 *
				 * @param {number|string} value Attachment
				 */
				function setAttachmentDataAndRenderContent( value ) {
					var hasAttachmentData = $.Deferred();

					if ( control.extended( api.UploadControl ) ) {
						hasAttachmentData.resolve();
					} else {
						value = parseInt( value, 10 );
						if ( _.isNaN( value ) || value <= 0 ) {
							delete control.params.attachment;
							hasAttachmentData.resolve();
						} else if ( control.params.attachment && control.params.attachment.id === value ) {
							hasAttachmentData.resolve();
						}
					}

					// Fetch the attachment data.
					if ( 'pending' === hasAttachmentData.state() ) {
						wp.media.attachment( value ).fetch().done( function() {
							control.params.attachment = this.attributes;
							hasAttachmentData.resolve();

							// Send attachment information to the preview for possible use in `postMessage` transport.
							wp.customize.previewer.send( control.setting.id + '-attachment-data', this.attributes );
						} );
					}

					hasAttachmentData.done( function() {
						control.renderContent();
					} );
				}

				// Ensure attachment data is initially set (for dynamically-instantiated controls).
				setAttachmentDataAndRenderContent( control.setting() );

				// Update the attachment data and re-render the control when the setting changes.
				control.setting.bind( setAttachmentDataAndRenderContent );
			};
		}

		ControlConstructor = ControlConstructor.extend( controlExtensions );
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
		fieldset = container.find( 'fieldset.control' );
		fieldset.find( '> ul' ).append( control.container );

		setting.validity.bind( function( valid ) {
			fieldset.toggleClass( 'invalid', ! valid );
		} );

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
