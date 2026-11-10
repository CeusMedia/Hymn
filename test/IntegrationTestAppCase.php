<?php

/**
 *	Base for integration tests running built hymn.phar within temporary apps.
 *	Apps use fixture modules of test/fixtures/source/ and can have a faked Composer installation.
 *	Another PHAR file can be tested by environment variable HYMN_PHAR.
 */
abstract class Hymn_IntegrationTest_AppCase extends Hymn_IntegrationTest_Case
{
	protected string $app;
	protected string $source;

	protected function setUp(): void
	{
		$this->app		= sys_get_temp_dir().'/hymn-test-app-'.uniqid().'/';
		$this->source	= (string) realpath( __DIR__.'/fixtures/source' ).'/';
		if( !file_exists( __DIR__.'/../vendor/composer/InstalledVersions.php' ) )
			self::markTestSkipped( 'Composer dependencies are not installed' );
	}

	protected function tearDown(): void
	{
		exec( 'rm -rf '.escapeshellarg( $this->app ) );
	}

	//  --  PROTECTED  --  //

	/**
	 *	Creates app folder with Hymn file and faked Composer installation.
	 *	@param		string|NULL					$uri			Application URI, NULL for app folder, empty for none
	 *	@param		array<string,string>|NULL	$packages		Installed packages (name => version), NULL for no Composer
	 *	@param		bool						$copySource		Copy fixture source into app, to be changed by test
	 */
	protected function createApp( ?string $uri = NULL, string $installType = 'link', ?array $packages = [], bool $copySource = FALSE ): void
	{
		exec( 'rm -rf '.escapeshellarg( $this->app ) );
		mkdir( $this->app.'config', 0775, TRUE );
		$this->source	= (string) realpath( __DIR__.'/fixtures/source' ).'/';
		if( $copySource ){																			//  source to be changed by test, eg. for updates
			exec( 'cp -R '.escapeshellarg( $this->source ).' '.escapeshellarg( $this->app.'source' ) );
			$this->source	= $this->app.'source/';
		}
		file_put_contents( $this->app.'.hymn', json_encode( [
			'application'	=> [
				'title'			=> 'Test App',
				'url'			=> 'http://localhost/',
				'uri'			=> $uri ?? $this->app,
				'installMode'	=> 'dev',
				'installType'	=> $installType,
			],
			'sources'		=> ['Fixtures' => ['active' => TRUE, 'type' => 'folder', 'path' => $this->source]],
			'modules'		=> new stdClass(),
		], JSON_PRETTY_PRINT ) );

		if( NULL === $packages )
			return;
		mkdir( $this->app.'vendor/composer', 0775, TRUE );
		copy( __DIR__.'/../vendor/composer/InstalledVersions.php', $this->app.'vendor/composer/InstalledVersions.php' );
		$versions	= [];
		foreach( $packages as $name => $version )
			$versions[$name]	= ['pretty_version' => $version, 'version' => $version, 'reference' => NULL, 'type' => 'library', 'install_path' => $this->app.'vendor/'.$name, 'aliases' => [], 'dev_requirement' => FALSE];
		$installed	= [
			'root'		=> ['name' => 'test/app', 'pretty_version' => '1.0.0', 'version' => '1.0.0.0', 'reference' => NULL, 'type' => 'project', 'install_path' => $this->app, 'aliases' => [], 'dev' => FALSE],
			'versions'	=> $versions,
		];
		file_put_contents( $this->app.'vendor/composer/installed.php', '<?php return '.var_export( $installed, TRUE ).';' );
	}

	/**
	 *	Runs hymn.phar within app folder (or a subfolder of it).
	 *	Given input is piped as answers for questions, one per line. Exit code is 124 on timeout.
	 */
	protected function runInApp( string $arguments, string $subfolder = '', string $input = '', int $timeout = 30 ): Hymn_IntegrationTest_HymnResponse
	{
		$phar		= getenv( 'HYMN_PHAR' ) ?: realpath( __DIR__.'/../hymn.phar' );				//  other PHAR file by environment
		$inputFile	= tempnam( sys_get_temp_dir(), 'hymn-test-input-' );						//  answers for questions, one per line
		file_put_contents( $inputFile, $input );
		$command	= vsprintf( 'cd %s && timeout %d %s %s < %s 2>&1', [
			escapeshellarg( $this->app.$subfolder ),
			$timeout,
			escapeshellarg( (string) $phar ),
			$arguments,
			escapeshellarg( $inputFile ),
		] );
		exec( $command, $output, $code );
		unlink( $inputFile );
		return new Hymn_IntegrationTest_HymnResponse( $code, $output );
	}

	protected function describe( Hymn_IntegrationTest_HymnResponse $result ): string
	{
		return 'Exit code '.$result->getCode().', output:'.PHP_EOL.implode( PHP_EOL, $result->getLines() );
	}

	/**
	 *	Changes module in (copied) source: sets version and replaces relations.
	 *	@param		string			$moduleId		eg. Test_Plain
	 *	@param		string			$version		New version
	 *	@param		list<string>	$needs			IDs of needed modules
	 */
	protected function changeSourceModule( string $moduleId, string $version, array $needs = [] ): void
	{
		$file	= $this->source.str_replace( '_', '/', $moduleId ).'/module.xml';
		$xml	= (string) file_get_contents( $file );
		$xml	= (string) preg_replace( '@<version>.+</version>@', '<version>'.$version.'</version>', $xml );
		$xml	= (string) preg_replace( '@(<log version="1.0.0">.+</log>)@', '$1'.PHP_EOL."\t".'<log version="'.$version.'">Update.</log>', $xml );
		$relations	= implode( '', array_map( static fn( string $id ) => PHP_EOL."\t\t".'<needs type="module">'.$id.'</needs>', $needs ) );
		$xml	= (string) preg_replace( '@<relations>.*</relations>@s', '<relations>'.$relations.PHP_EOL."\t".'</relations>', $xml );
		file_put_contents( $file, $xml );
	}

	/**
	 *	Returns installed version of module, read from module configuration of app.
	 */
	protected function getInstalledVersion( string $moduleId ): ?string
	{
		$file	= $this->app.'config/modules/'.$moduleId.'.xml';
		if( !file_exists( $file ) )
			return NULL;
		preg_match( '@<version[^>]*>([^<]+)</version>@', (string) file_get_contents( $file ), $matches );
		return $matches[1] ?? NULL;
	}

	/**
	 *	Changes Hymn file of app by callback, getting and returning decoded data as array.
	 *	@param		callable(array<string,mixed>): array<string,mixed>		$change
	 */
	protected function changeHymnFile( callable $change ): void
	{
		$data	= json_decode( (string) file_get_contents( $this->app.'.hymn' ), TRUE );
		$data	= $change( $data );
		if( [] === $data['modules'] )
			$data['modules']	= new stdClass();
		file_put_contents( $this->app.'.hymn', json_encode( $data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES ) );
	}
}
