module.exports = {
	options: {
		jshintrc: 'config/.jshintrc'
	},
	check: [
		'admin/assets/js/*.js',
		'admin/assets/js/**/*.js',
		'!admin/assets/js/*.bundle.js',
		'!admin/assets/js/*.min.js'
	],
	grunt: {
		options: {
			jshintrc: 'config/.jshintnoderc'
		},
		src: [
			'gruntfile.js',
			'shipitfile.js',
			'config/grunt/*.js'
		]
	}
};
