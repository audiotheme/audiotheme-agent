/*global _:false, _audiothemeAgentSettings:false, Backbone:false, jQuery:false, wp:false */

(function( window, $, _, Backbone, wp, undefined ) {
	'use strict';

	var app = {},
		settings = _audiothemeAgentSettings;

	_.extend( app, { controller: {}, model: {}, view: {} } );

	/**
	 * ========================================================================
	 * CONTROLLERS
	 * ========================================================================
	 */

	app.controller.SubscriptionManager = Backbone.Model.extend({
		defaults: {
			subscriptions: null
		},

		subscribe: function( token ) {
			var self = this;

			return wp.ajax.post( 'audiotheme_agent_subscribe', {
				token: token,
				nonce: settings.nonces.subscribe
			}).done(function( response ) {
				self.get( 'subscriptions' ).reset( response );
			}).fail(function( response ) {

			});
		}
	});

	/**
	 * ========================================================================
	 * MODELS
	 * ========================================================================
	 */

	app.model.Subscription = Backbone.Model.extend({
		defaults: {
			id: ''
		},

		destroy: function ( options ) {
			this.trigger( 'destroy', this, this.collection, options );
			if ( options && options.success ) {
				options.success();
			}
		},

		disconnect: function() {
			var self = this;

			return wp.ajax.post( 'audiotheme_agent_disconnect_subscription', {
				id: this.get( 'id' ),
				nonce: settings.nonces.disconnect
			}).done(function() {
				self.destroy();
			}).always(function( response ) {

			});
		}
	});

	app.model.Subscriptions = Backbone.Collection.extend({
		model: app.model.Subscription
	});

	/**
	 * ========================================================================
	 * VIEWS
	 * ========================================================================
	 */

	app.view.TokenGroup = wp.Backbone.View.extend({
		events: {
			'click button': 'subscribe',
			'keyup input': 'toggleButton'
		},

		initialize: function( options ) {
			this.controller = options.controller;
		},

		render: function() {
			this.$button = this.$el.find( '.button' );
			this.$feedback = this.$el.find( '.audiotheme-agent-subscription-token-group-feedback' );
			this.$field = this.$el.find( 'input' );
			this.$spinner = $( '<span class="spinner"></span>' ).insertAfter( this.$button );

			this.toggleButton();

			return this;
		},

		subscribe: function( e ) {
			var view = this;

			e.preventDefault();

			if ( '' === this.$field.val() ) {
				return;
			}

			this.$spinner.addClass( 'is-active' );

			this.controller
				.subscribe( this.$field.val() )
				.done(function() {
					view.$field.val( '' );
				})
				.fail(function( response ) {
					if ( 'message' in response ) {
						view.$feedback.text( response.message );
					}
				})
				.always(function( response ) {
					view.$spinner.removeClass( 'is-active' );
				});

		},

		toggleButton: function() {
			this.$button.prop( 'disabled', '' === this.$field.val() );
		}
	});

	app.view.SubscriptionsTable = wp.Backbone.View.extend({
		initialize: function( options ) {
			this.controller = options.controller;
			this.listenTo( this.controller.get( 'subscriptions' ), 'add', this.addRow );
			this.listenTo( this.controller.get( 'subscriptions' ), 'reset', this.render );
		},

		render: function() {
			var subscriptions = this.controller.get( 'subscriptions' );

			this.$tbody = this.$el.find( 'tbody' );

			if ( subscriptions.length ) {
				this.$tbody.html( '' );
				subscriptions.each( this.addRow, this );
			}
			return this;
		},

		addRow: function( model ) {
			this.$tbody.append(
				new app.view.SubscriptionsTableRow({
					model: model
				}).render().el
			);
		}
	});

	app.view.SubscriptionsTableRow = wp.Backbone.View.extend({
		tagName: 'tr',
		template: wp.template( 'audiotheme-agent-subscriptions-table-row' ),

		events: {
			'click .js-disconnect-subscription': 'disconnect'
		},

		initialize: function( options ) {
			this.model = options.model;
			this.listenTo( this.model, 'destroy', this.remove );
		},

		render: function() {
			this.$el.html( this.template( _.extend( this.model.toJSON(), {
				nextPaymentDate: function( dateString ) {
					var date = new Date( dateString );
					return date.getFullYear() + '-' + ( date.getMonth() + 1 ) + '-' + date.getDate();
				}
			} ) ) );
			return this;
		},

		disconnect: function( e ) {
			e.preventDefault();
			this.model.disconnect();
		},

		remove: function() {
			this.$el.remove();
		}
	});

	/**
	 * ========================================================================
	 * SETUP
	 * ========================================================================
	 */

	var controller = new app.controller.SubscriptionManager({
		subscriptions: new app.model.Subscriptions( settings.subscriptions )
	});

	new app.view.TokenGroup({
		el: $( '.audiotheme-agent-subscription-token-group' ).get( 0 ),
		controller: controller
	}).render();

	new app.view.SubscriptionsTable({
		el: $( '.audiotheme-agent-subscriptions' ).get( 0 ),
		controller: controller
	}).render();

})( window, jQuery, _, Backbone, wp );
