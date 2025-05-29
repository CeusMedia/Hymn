<?php
/*  --  REQUIREMENTS  --  */
require_once __DIR__.'/../src/classes/Tool/Test.php';
require_once __DIR__.'/../src/classes/Client.php';

/*  --  ARGUMENTS  --  */
$modes		= ['prod', 'dev'];

$options	= array_merge( [
	'mode'		=> 'prod',
	'php'		=> '/usr/bin/env php',
], getopt( "", preg_split( '/\|/', 'mode:|locale:|php:' ) ) );
$options['mode']	= in_array( $options['mode'], $modes ) ? $options['mode'] : $modes[0];

//  Plesk: absolute path of specific version
//$options['php']		= '/opt/plesk/php/8.1/bin/php';


print('Mode: '.$options['mode'].PHP_EOL);
print('PHP:  '.$options['php'].PHP_EOL);
//print('Term: '.getEnv( 'TERM' ).PHP_EOL);

/*  --  SETUP  --  */
$cols			= ( $cols = intval( `tput cols -T xterm-256color` ) ) ? $cols : 80;
$rootPath		= dirname( __DIR__ );
$pharFileName	= 'hymn.phar';
$pharFilePath	= $rootPath.'/'.$pharFileName;
$mainFileName	= 'hymn.php';
$stubFileName	= __DIR__.'/'.'stub.php';
$filesToAdd		= [];
$pathsToAdd		= ['locales', 'templates'];

foreach( $pathsToAdd as $pathToAdd ){
	$directory	= new RecursiveDirectoryIterator( $rootPath."/src/".$pathToAdd, RecursiveDirectoryIterator::SKIP_DOTS );
	$iterator	= new RecursiveIteratorIterator( $directory, RecursiveIteratorIterator::CHILD_FIRST );
	$pattern	= '/^'.preg_quote( $rootPath."/src/", '/' ).'/';
	foreach( $iterator as $entry )
		if( !$entry->isDir() )
			$filesToAdd[]	= preg_replace( $pattern, '', $entry->getPathname() );
}

/*  --  CREATION  --  */
$pharFlags	= FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME;
$archive	= new Phar( $pharFilePath, $pharFlags, $pharFileName );
$stub		= $options['mode'] === 'prod' ? php_strip_whitespace( $stubFileName ) : file_get_contents( $stubFileName );
$stub		= strtr( $stub, ['[pharFileName]' => $pharFileName, '[mainFileName]' => $mainFileName] );

$archive->startBuffering();
$archive->setStub( "#!".trim( $options['php'] ).PHP_EOL.$stub );
$archive->addFromString( $mainFileName, file_get_contents( __DIR__.'/'.$mainFileName ) );
foreach( $filesToAdd as $item )
	$archive->addFile( $rootPath.'/src/'.$item, $item );

file_put_contents( $rootPath."/build/.mode", $options['mode'] );
file_put_contents( $rootPath."/build/.php", $options['php'] );
Hymn_Client::$phpPath	= $options['php'];

shell_exec( "test -d ".$rootPath."/build/classes && rm -rf ".$rootPath."/build/classes || true" );
shell_exec( "cp -r ".$rootPath."/src/classes ".$rootPath."/build/" );

$directory	= new RecursiveDirectoryIterator( $rootPath."/build/classes", RecursiveDirectoryIterator::SKIP_DOTS );
$iterator	= new RecursiveIteratorIterator( $directory, RecursiveIteratorIterator::CHILD_FIRST );
$nrFiles	= 0;
foreach( $iterator as $entry )
	if( !$entry->isDir() )
		$nrFiles++;

$count	= 0;
foreach( $iterator as $entry ){
	if( !$entry->isDir() ){
		$count++;
		$filePath	= $entry->getPathname();
		$message	= vsprintf( "\r".'[%4$s%%] %1$s', [
			str_replace( $rootPath.'/build/', 'src/', $filePath ),
			$count,
			$nrFiles,
			str_pad( ceil( $count / $nrFiles * 100 ), 3, ' ', STR_PAD_LEFT ),
		] );
		print( str_pad( $message, $cols - 2, ' ' ) );
		$syntax	= Hymn_Tool_Test::staticCheckPhpFileSyntax( $filePath );
		if( !$syntax->valid ){
			$message	= str_replace( $rootPath.'/build/', 'src/', $syntax->message );
			print( "\r".str_pad( 'FAIL: '.$message, $cols - 2, ' ' ).PHP_EOL );
			exit( 1 );
		}
		if( $options['mode'] !== 'dev' )
			file_put_contents( $filePath, trim( php_strip_whitespace( $filePath ) ) );
	}
}
print( "\r".str_repeat( ' ', $cols - 2 )."\r" );

$archive->buildFromDirectory( $rootPath.'/build/classes/', '$(.*)\.php$' );
$archive->addFile( $rootPath.'/CHANGELOG.md', '.changelog' );
$archive->addFile( $rootPath.'/src/baseArgumentOptions.json', 'baseArgumentOptions.json' );
$archive->addFile( $rootPath.'/build/.mode', '.mode' );
$archive->addFile( $rootPath.'/build/.php', '.php' );
if( $options['mode'] !== 'dev' )
	$archive->compressFiles( Phar::GZ );
$archive->stopBuffering();
shell_exec( "rm -rf ".$rootPath."/build/classes" );
require_once __DIR__.'/../src/classes/Client.php';
print( vsprintf( 'Done building version %3$s-%4$s into %1$s (%2$s).'.PHP_EOL, [
	$pharFileName,
	round( filesize( $pharFileName ) / 1024, 1 ).'kB',
	Hymn_Client::$version,
	$options['mode'],
] ) );
