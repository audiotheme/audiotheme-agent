module.exports = {
	package: {
		options: {
			archive: 'dist/<%= package.name %>-<%= package.version %>.zip'
		},
		files: [
			{
				src: [
					'**',
					'!coverage/**',
					'!config/**',
					'!dist/**',
					'!node_modules/**',
					'!tests/**',
					'!vendor/**',
					'!.editorconfig',
					'!.esformatter',
					'!.gitignore',
					'!composer.*',
					'!Gruntfile.js',
					'!package.json',
					'!phpcs.log',
					'!phpcs.xml',
					'!phpunit.xml',
					'!README.md',
					'!screenshot-1.png',
					'!shipitfile.js'
				],
				dest: '<%= package.name %>/'
			}
		]
	}
};
