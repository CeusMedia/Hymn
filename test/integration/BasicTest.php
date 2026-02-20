<?php

class Hymn_IntegrationTest_BasicTest extends Hymn_IntegrationTest_Case
{
	protected Hymn_Client $client;

	protected function setUp(): void
	{
	}

	public function testVersion(): void
	{
		$result	= $this->runHymn( 'version' );
//		print_r($result);

		$code	= $result->getCode();
		$this->assertEquals( 0, $code, 'EXECUTING hymn.phar FAILED WITH CODE '.$code.' AND OUTPUT: '.PHP_EOL.join( PHP_EOL, $result->getLines() ) );
		$this->assertTrue( $result->isSuccess() );
		$this->assertFalse( $result->isFailure() );


		$this->assertTrue( $result->hasLines() );
		$this->assertEquals( 1, $result->countLines() );

		list( $builtVersion ) = explode( ' ', $result->getFirstLine(), 2 );
		$buildVersion	= Hymn_Client::$version.'-'.$this->getEnvModeFromBuild();
		$mismatchMessage	= 'YOUR ARE TESTING AGAINST THE WRONG VERSION - this hymn.phar is not built by current Hymn_Client::$version and env mode, set in ./build/.mode';
		$this->assertEquals(  $buildVersion, $builtVersion, $mismatchMessage );
	}

	public function testContext(): void
	{
		$this->createContextFromTemplate( '01-FontAwesome', 'ctx_01-FontAwesome', TRUE );
		$result	= $this->runHymn( 'app-info' );
//		print_r( $result->getLines() );

		$resultBlock	= join( PHP_EOL, $result->getLines() );
		$this->assertStringContainsString( 'title => My Project', $resultBlock );


		$result	= $this->runHymn( 'app-install UI_Font_Fira' );
		print_r( $result->getLines() );
	}
}