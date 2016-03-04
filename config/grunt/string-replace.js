module.exports = {
	package: {
		options: {
			replacements: [
				{
					pattern: /(Version:[\s]+).+/,
					replacement: '$1<%= package.version %>'
				},
				{
					pattern: /'AUDIOTHEME_AGENT_VERSION', '[^']+'/,
					replacement: '\'AUDIOTHEME_AGENT_VERSION\', \'<%= package.version %>\''
				}
			]
		},
		files: {
			'audiotheme-agent.php': 'audiotheme-agent.php'
		}
	}
};
