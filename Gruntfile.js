module.exports = function( grunt ) {
	'use strict';

	require('phplint').gruntPlugin(grunt);
	grunt.initConfig( {

		pkg: grunt.file.readJSON( 'package.json' ),

		phpcs: {
			plugin: {
				src: './'
			},
			options: {
				bin: "vendor/bin/phpcs --extensions=php --ignore=\"*/vendor/*,*/node_modules/*\"",
				standard: "phpcs.ruleset.xml"
			}
		},

		phplint: {
			options: {
				limit: 10,
				stdout: true,
				stderr: true
			},
			files: ['tests/*.php', '*.php']
		},

		phpunit: {
			'default': {
				cmd: 'phpunit',
				args: [ '-c', 'phpunit.xml.dist' ],
			}
		}

	} );
	grunt.loadNpmTasks( 'grunt-phpcs' );

	// Testing tasks.
	grunt.registerMultiTask('phpunit', 'Runs PHPUnit tests, including the ajax, external-http, and multisite tests.', function() {
		grunt.util.spawn({
			cmd: this.data.cmd,
			args: this.data.args,
			opts: {stdio: 'inherit'}
		}, this.async());
	});

	grunt.registerTask( 'test', [ 'phpcs', 'phplint', 'phpunit' ] );
	grunt.util.linefeed = '\n';

	// Travis CI tasks.
	grunt.registerTask('travis:phpvalidate', 'Runs PHPUnit Travis CI PHP code tasks.', [
		'phpcs',
		'phplint'
	] );
	grunt.registerTask('travis:phpunit', 'Runs PHPUnit Travis CI tasks.', [
		'phpunit',
	] );
};
