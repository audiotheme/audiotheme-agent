module.exports = {
	package: {
		options: {
			replacements: [
				{
					pattern: /(Version:[\s]+).+/,
					replacement: '$1<%= package.version %>'
				}
			]
		},
		files: {
			'audiotheme-agent.php': 'audiotheme-agent.php'
		}
	}
};
