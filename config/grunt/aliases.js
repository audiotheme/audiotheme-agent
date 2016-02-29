module.exports = function( grunt, options ) {
	return {
		'default': [],
		'check': [
			'jshint'
		],
		'package': [
			'check',
			'string-replace:package',
			'makepot',
			'compress:package'
		]
	};
};
