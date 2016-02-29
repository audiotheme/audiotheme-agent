/*global Promise:false */

var aws = require('aws-sdk'),
	fs = require('fs'),
	project = require( __dirname + '/package.json' );

module.exports = function ( shipit ) {
	shipit.initConfig({
		aws: {
			servers: 's3',
			bucket: 'audiotheme-packages'
		}
	});

	shipit.task( 'release', function( callback ) {
		var credentials, s3,
			archiveFile = project.name + '-' + project.version + '.zip',
			uploadPath = project.name + '/' + archiveFile;

		if ( 'aws' !== shipit.environment || 's3' !== shipit.config.servers ) {
			throw new Error( 'The release task only works with Amazon S3.' );
		}

		credentials = new aws.SharedIniFileCredentials({
			profile: shipit.config.profile || 'shipit'
		});

		s3 = new aws.S3({
			credentials: credentials,
			region: shipit.config.region || 'us-east-1'
		});

		shipit.log( 'Uploading "dist/%s" to "%s"', archiveFile, uploadPath );

		return new Promise(function( resolve, reject ) {
			s3.putObject({
				ACL: 'public-read',
				Bucket: shipit.config.bucket,
				Key: uploadPath,
				Body: fs.readFileSync( 'dist/' + archiveFile ),
				ContentType: 'application/zip',
			}, function( error, response ) {
				if ( error ) {
					reject( error );
				} else {
					resolve( uploadPath );
				}
			});
		});
	});
};
