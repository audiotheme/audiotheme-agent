/*jshint node:true */

module.exports = function( grunt ) {
	'use strict';

	grunt.loadNpmTasks( 'grunt-contrib-jshint');
	grunt.loadNpmTasks( 'grunt-wp-i18n');

	grunt.initConfig({

		addtextdomain: {
			options: {
				updateDomains: [ 'all' ]
			},
			plugin: {
				files: {
					src: [
						'*.php',
						'**/*.php',
						'!node_modules/**'
					]
				}
			}
		},

		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},
			plugin: [
				'Gruntfile.js',
				'admin/assets/js/*.js',
				'!admin/assets/js/*.min.js',
			]
		},

		makepot: {
			plugin: {
				options: {
					mainFile: 'audiotheme-agent.php',
					potHeaders: {
						poedit: true
					},
					type: 'wp-plugin',
					updatePoFiles: true,
					updateTimestamp: false
				}
			}
		}

	});

	grunt.registerTask( 'default', [ 'jshint' ] );

};
