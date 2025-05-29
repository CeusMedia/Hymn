<?php
class Hymn_Test_Module_Reader2Test extends PHPUnit_Framework_TestCase
{
	public function testDecorateObjectWithConfig()
	{
		$xmlFile	= 'test/assets/moduleTest.xml';
		$module	= Hymn_Module_Reader2::load( $xmlFile, 'Test' );
//		print( json_encode( $module, JSON_PRETTY_PRINT ) );die;
		self::assertFalse( $module->config['text']->mandatory );
	}

}
