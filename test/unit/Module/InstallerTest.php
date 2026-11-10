<?php
class Hymn_Test_Module_InstallerTest extends PHPUnit_Framework_TestCase
{
	protected string $path;
	protected string $fileName;

	protected function setUp(): void
	{
		$this->fileName		= Hymn_Client::$fileName;
		$this->path			= sys_get_temp_dir().'/hymn-test-installer-'.uniqid().'/';
		mkdir( $this->path.'config', 0775, TRUE );
		Hymn_Client::$fileName	= $this->path.'.hymn';										//  options like --file are applied on run only
	}

	protected function tearDown(): void
	{
		Hymn_Client::$fileName	= $this->fileName;
		exec( 'rm -rf '.escapeshellarg( $this->path ) );
	}

	public function testGetApplicationPathFromApplicationUri(): void
	{
		$installer	= $this->createInstaller( '/var/www/myApp' );
		self::assertSame( '/var/www/myApp/', $installer->getApplicationPath() );

		$installer	= $this->createInstaller( '/var/www/myApp/' );
		self::assertSame( '/var/www/myApp/', $installer->getApplicationPath() );
	}

	public function testGetApplicationPathFallsBackToWorkingDirectory(): void
	{
		$installer	= $this->createInstaller( '' );
		self::assertSame( rtrim( (string) getcwd(), '/' ).'/', $installer->getApplicationPath() );

		$installer	= $this->createInstaller( NULL );
		self::assertSame( rtrim( (string) getcwd(), '/' ).'/', $installer->getApplicationPath() );
	}

	/**
	 *	Creates installer with Hymn file having given application URI (or none, if NULL).
	 *	Protected method getApplicationPath is made public by anonymous subclass.
	 */
	protected function createInstaller( ?string $uri ): object
	{
		$application	= ['title' => 'Test'];
		if( NULL !== $uri )
			$application['uri']	= $uri;
		file_put_contents( $this->path.'.hymn', json_encode( ['application' => $application, 'sources' => [], 'modules' => []] ) );
		$client		= new Hymn_Client( ['--quiet'], FALSE );
		$library	= new Hymn_Module_Library( $client );
		return new class( $client, $library ) extends Hymn_Module_Installer {
			public function getApplicationPath(): string
			{
				return parent::getApplicationPath();
			}
		};
	}
}
