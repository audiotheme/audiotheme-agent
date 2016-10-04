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

	app.controller.Manager = Backbone.Model.extend({
		defaults: {
			packages: null,
			subscriptions: null
		},

		createChildTheme: function( slug ) {
			var self = this,
				model = this.get( 'packages' ).get( slug );

			return wp.ajax.post( 'audiotheme_agent_create_child_theme', {
				nonce: model.get( 'child_theme_nonce' ),
				slug: model.get( 'slug' )
			}).done(function( response ) {
				self.get( 'packages' ).add( response.package, { merge: true });
			});
		},

		install: function( slug ) {
			var self = this,
				model = this.get( 'packages' ).get( slug );

			return wp.ajax.post( 'audiotheme_agent_install_package', {
				nonce: model.get( 'install_nonce' ),
				slug: model.get( 'slug' )
			}).done(function( response ) {
				self.get( 'packages' ).add( response.package, { merge: true });
			});
		},

		subscribe: function( token ) {
			var self = this;

			return wp.ajax.post( 'audiotheme_agent_subscribe', {
				token: token,
				nonce: settings.nonces.subscribe
			}).done(function( response ) {
				self.get( 'packages' ).reset( response.packages );
				self.get( 'subscriptions' ).reset( response.subscriptions );
			});
		}
	});

	/**
	 * ========================================================================
	 * MODELS
	 * ========================================================================
	 */

	app.model.Package = Backbone.Model.extend({
		idAttribute: 'slug',

		defaults: {
			name: '',
			slug: '',
			changelog_url: '',
			current_version: '',
			homepage: '',
			is_installed: false,
			install_nonce: '',
			installed_version: '',
			type: '',
			type_label: '',
			action_button: '',
			is_active: false,
			is_viewable: true,
			has_access: false,
			has_update: false
		}
	});

	app.model.Packages = Backbone.Collection.extend({
		model: app.model.Package
	});

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

	app.view.PackagesTable = wp.Backbone.View.extend({
		template: wp.template( 'audiotheme-agent-packages-table' ),

		initialize: function( options ) {
			this.controller = options.controller;
			this.title = options.title;
			this.type = options.type;

			this.listenTo( this.controller.get( 'packages' ), 'add', this.addRow );
			this.listenTo( this.controller.get( 'packages' ), 'reset', this.render );
		},

		render: function() {
			var packages = this.controller.get( 'packages' );

			packages = packages.where({
				is_viewable: true,
				type: this.type
			}).filter(function( model ) {
				return model.get( 'is_installed' ) || model.get( 'has_access' );
			});

			this.$el.html( this.template({ title: this.title }) );
			this.$tbody = this.$( 'tbody' ).empty();

			if ( packages.length ) {
				_.each( packages, function( model ) {
					this.addRow( model );
				}, this );
				this.$el.show();
			} else {
				this.$el.hide();
			}

			return this;
		},

		addRow: function( model ) {
			var row = new app.view.PackagesTableRow({
				controller: this.controller,
				model: model
			});

			this.$tbody.append( row.render().el );
		}
	});

	app.view.PackagesTableRow = wp.Backbone.View.extend({
		tagName: 'tr',
		template: wp.template( 'audiotheme-agent-packages-table-row' ),

		events: {
			'click .js-install': 'installPackage',
			'click .js-create-child': 'createChildTheme'
		},

		initialize: function( options ) {
			this.controller = options.controller;
			this.model = options.model;
			this.listenTo( this.model, 'change', this.render );
		},

		render: function() {
			this.$spinner = $( '<span class="spinner" />' );
			this.$el.html( this.template( this.model.toJSON() ) );

			if ( this.model.get( 'has_update' ) ) {
				this.$el.addClass( 'has-update' );
			} else if ( this.model.get( 'is_active' ) ) {
				this.$el.addClass( 'is-active' );
			}

			if ( this.model.get( 'is_installed' ) ) {
				this.$el.addClass( 'is-installed' );
			}

			if ( this.model.get( 'has_access' ) ) {
				this.$el.addClass( 'is-downloadable' );
			}

			return this;
		},

		createChildTheme: function( e ) {
			var $row = $( e.target ).closest( 'tr' ),
				$column = $( e.target ).closest( '.column-action' );

			e.preventDefault();

			$column.find( '.button' ).first()
				.prop( 'disabled', true )
				.addClass( 'button-disabled' )
				.text( 'Creating child theme...' );

			$column.prepend( this.$spinner.addClass( 'is-active' ) );

			this.controller.createChildTheme( this.model.get( 'slug' ) )
				.fail(function( response ) {
					if ( 'message' in response ) {
						$column.append( '<span class="error-message" />' )
							.find( '.error-message' ).text( response.message );
					}
				})
				.done(function() {
					$row.find( '.audiotheme-agent-dropdown-toggle' ).click();
				});
		},

		installPackage: function( e ) {
			var $button = $( e.target ).prop( 'disabled', true ).addClass( 'button-disabled' ),
				$column = $button.closest( '.column-action' );

			e.preventDefault();

			$column.prepend( this.$spinner.addClass( 'is-active' ) );

			this.controller.install( this.model.get( 'slug' ) )
				.fail(function( response ) {
					if ( 'message' in response ) {
						$column.append( '<span class="error-message" />' )
							.find( '.error-message' ).text( response.message );
					}
				});
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

			this.$tbody = this.$( 'tbody' );

			if ( subscriptions.length ) {
				this.$tbody.empty();
				subscriptions.each( this.addRow, this );
			}
			return this;
		},

		addRow: function( model ) {
			var row = new app.view.SubscriptionsTableRow({
				controller: this.controller,
				model: model
			});

			this.$tbody.append( row.render().el );
		}
	});

	app.view.SubscriptionsTableRow = wp.Backbone.View.extend({
		tagName: 'tr',
		template: wp.template( 'audiotheme-agent-subscriptions-table-row' ),

		events: {
			'click .js-disconnect-subscription': 'disconnect'
		},

		initialize: function( options ) {
			this.controller = options.controller;
			this.model = options.model;
			this.listenTo( this.model, 'destroy', this.remove );
		},

		render: function() {
			this.$spinner = $( '<span class="spinner" />' );
			this.$el.html( this.template( _.extend( this.model.toJSON(), {
				nextPaymentDate: function( dateString ) {
					if ( ! dateString ) {
						return '';
					}

					return dateString.match( /[0-9]{4}-[0-9]{2}-[0-9]{2}/ ).pop();
				}
			} ) ) );

			return this;
		},

		disconnect: function( e ) {
			var view = this,
				$button = $( e.target ).prop( 'disabled', true ).addClass( 'button-disabled' );

			e.preventDefault();

			$button.before( this.$spinner.addClass( 'is-active' ) );

			this.model.disconnect().done(function( response ) {
				view.controller.get( 'packages' ).reset( response.packages );
			}).always(function() {
				view.$spinner.removeClass( 'is-active' );
			});
		}/*,

		remove: function() {
			this.$el.remove();
		}*/
	});

	app.view.TokenGroup = wp.Backbone.View.extend({
		events: {
			'click button': 'subscribe',
			'input input': 'toggleButton'
		},

		initialize: function( options ) {
			this.controller = options.controller;
		},

		render: function() {
			this.$button = this.$( '.button' );
			this.$feedback = this.$( '.audiotheme-agent-subscription-token-group-feedback' );
			this.$field = this.$( 'input' );
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

			this.$button.prop( 'disabled', true );
			this.$spinner.addClass( 'is-active' );

			this.controller
				.subscribe( this.$field.val() )
				.done(function() {
					view.$field.val( '' );
					view.$feedback.hide().text( '' );
				})
				.fail(function( response ) {
					if ( 'message' in response ) {
						view.$feedback.text( response.message );
					}
				})
				.always(function( response ) {
					view.toggleButton();
					view.$spinner.removeClass( 'is-active' );
				});

		},

		toggleButton: function() {
			this.$button.prop( 'disabled', '' === this.$field.val() );
		}
	});

	/**
	 * ========================================================================
	 * SETUP
	 * ========================================================================
	 */

	var controller, pluginsTable, themesTable;

	controller = new app.controller.Manager({
		packages: new app.model.Packages( settings.packages ),
		subscriptions: new app.model.Subscriptions( settings.subscriptions )
	});

	new app.view.TokenGroup({
		el: $( '.audiotheme-agent-subscription-token-group' ),
		controller: controller
	}).render();

	new app.view.SubscriptionsTable({
		el: $( '.audiotheme-agent-subscriptions' ),
		controller: controller
	}).render();

	pluginsTable = new app.view.PackagesTable({
		controller: controller,
		title: settings.l10n.plugins,
		type: 'plugin'
	});

	themesTable = new app.view.PackagesTable({
		controller: controller,
		title: settings.l10n.themes,
		type: 'theme'
	});

	$( '.wrap' )
		.append( pluginsTable.render().el )
		.append( themesTable.render().el );

	$( '.audiotheme-agent-client-notice' ).on( 'click', 'a', function( e ) {
		e.preventDefault();
		$( '#contextual-help-link' ).click();
	});

	$( document )
		.on( 'click', '.audiotheme-agent-dropdown-toggle', function( e ) {
			e.preventDefault();
			$( this ).closest( '.audiotheme-agent-dropdown-group' ).toggleClass( 'is-open' );
		})
		.on( 'click', function( e ) {
			if ( ! $( e.target ).closest( 'button' ).hasClass( 'audiotheme-agent-dropdown-toggle' ) ) {
				$( '.audiotheme-agent-dropdown-group' ).removeClass( 'is-open' );
			} else {
				var $group = $( e.target ).closest( '.audiotheme-agent-dropdown-group' );
				$( '.audiotheme-agent-dropdown-group' ).not( $group ).removeClass( 'is-open' );
			}
		});

})( window, jQuery, _, Backbone, wp );
