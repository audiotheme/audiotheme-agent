module.exports = {
	package: {
		options: {
			archive: 'dist/<%= package.name %>-<%= package.version %>.zip'
		},
		files: [
			{
				src: [
					'**',
					'!config/**',
					'!dist/**',
					'!node_modules/**',
					'!tests/**',
					'!vendor/**',
					'!.editorconfig',
					'!.esformatter',
					'!.gitignore',
					'!composer.*',
					'!gruntfile.js',
					'!package.json',
					'!phpcs.log',
					'!phpcs.xml',
					'!phpunit.xml',
					'!shipitfile.js'
				],
				dest: '<%= package.name %>/'
			}
		]
	}
};
