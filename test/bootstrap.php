<?php
//  load classes of Hymn on demand: Hymn_Tool_CLI_Question => src/classes/Tool/CLI/Question.php
spl_autoload_register( static function( string $className ): void {
	if( !str_starts_with( $className, 'Hymn_' ) )
		return;
	$file	= __DIR__.'/../src/classes/'.str_replace( '_', '/', substr( $className, 5 ) ).'.php';
	if( file_exists( $file ) )
		require_once $file;
} );

if( !class_exists( 'PHPUnit_Framework_TestCase' ) ){
	class PHPUnit_Framework_TestCase extends PHPUnit\Framework\TestCase{
	}
}

require_once __DIR__.'/IntegrationTestCase.php';
require_once __DIR__.'/IntegrationTestAppCase.php';
require_once __DIR__.'/IntegrationTestContext.php';
require_once __DIR__.'/IntegrationTestContextManager.php';
require_once __DIR__.'/IntegrationTestHymnResponse.php';
