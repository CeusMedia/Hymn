<?php
class Hymn_Test_Module_Reader2Test extends PHPUnit_Framework_TestCase
{
	public function testDecorateObjectWithConfig()
	{
		$xmlFile	= __DIR__.'/../assets/moduleTest.xml';
		$module	= Hymn_Module_Reader2::load( $xmlFile, 'Test' );
//		print( json_encode( $module, JSON_PRETTY_PRINT ) );die;
		self::assertFalse( $module->config['text']->mandatory );
	}

	public function testDecorateObjectWithRelations(): void
	{
		$xmlFile	= __DIR__.'/../assets/moduleRelationsTest.xml';
		$module		= Hymn_Module_Reader2::load( $xmlFile, 'TestRelations' );
		$needs		= $module->relations->needs;

		self::assertSame( ['Resource_Database', 'vendor/attribute', 'vendor/appended', 'vendor/both', 'vendor/without'], array_keys( $needs ) );
		self::assertSame( Hymn_Structure_Module_Relation::TYPE_MODULE, $needs['Resource_Database']->type );
		self::assertSame( Hymn_Structure_Module_Relation::TYPE_PACKAGE, $needs['vendor/attribute']->type );

		self::assertSame( '^3.0', $needs['vendor/attribute']->version );					//  version by attribute
		self::assertSame( '^2.6.2', $needs['vendor/appended']->version );				//  version appended to package name
		self::assertSame( 'vendor/appended', $needs['vendor/appended']->id );
		self::assertSame( '^1.0', $needs['vendor/both']->version );						//  attribute wins
		self::assertSame( '', $needs['vendor/without']->version );

		$supports	= $module->relations->supports;
		self::assertSame( ['vendor/supported'], array_keys( $supports ) );
		self::assertSame( '~1.2 || ^2.0', $supports['vendor/supported']->version );
	}
}
