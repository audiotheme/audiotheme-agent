module.exports = {
	build: {
		options: {
			updateDomains: [ 'all' ]
		},
		files: {
			src: [
				'*.php',
				'**/*.php',
				'!dist/**',
				'!node_modules/**',
				'!tests/**',
				'!vendor/**'
			]
		}
	}
};
