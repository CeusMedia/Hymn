<?php
/*  --  REQUIREMENTS  --  */
require_once __DIR__.'/../src/classes/Tool/Test.php';

/*  --  ARGUMENTS  --  */
$modes		= array( 'prod', 'dev' );
$options	= array_merge( array(
	'mode'		=> 'prod',
), getopt( "", preg_split( '/\|/', 'mode:|locale:' ) ) );
$options['mode']	= in_array( $options['mode'], $modes ) ? $options['mode'] : $modes[0];

/*  --  SETUP  --  */
$cols			= ( $cols = intval( `tput cols` ) ) ? $cols : 80;
$rootPath		= dirname( __DIR__ );
$pharFileName	= 'hymn.phar';
$pharFilePath	= $rootPath.'/'.$pharFileName;
$mainFileName	= 'hymn.php';
$stubFileName	= __DIR__.'/'.'stub.php';
$filesToAdd		= array();
$pathsToAdd		= array( 'locales', 'templates' );

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
$stub		= strtr( $stub, array( '[pharFileName]' => $pharFileName, '[mainFileName]' => $mainFileName ) );

$archive->startBuffering();
$archive->setStub( "#!/usr/bin/env php".PHP_EOL.$stub );
$archive->addFromString( $mainFileName, file_get_contents( __DIR__.'/'.$mainFileName ) );
foreach( $filesToAdd as $item )
	$archive->addFile( $rootPath.'/src/'.$item, $item );

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
		$message	= vsprintf( "\r".'[%4$s%%] %1$s', array(
			str_replace( $rootPath.'/build/', 'src/', $filePath ),
			$count,
			$nrFiles,
			str_pad( ceil( $count / $nrFiles * 100 ), 3, ' ', STR_PAD_LEFT ),
 		) );
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
//if( $options['mode'] !== 'dev' )
$archive->compressFiles( Phar::GZ );
$archive->stopBuffering();
shell_exec( "rm -rf ".$rootPath."/build/classes" );
require_once __DIR__.'/../src/classes/Client.php';
print( vsprintf( 'Done building version %3$s-%4$s into %1$s (%2$s).', array(
	$pharFileName,
	round( filesize( $pharFileName ) / 1024, 1 ).'kB',
	Hymn_Client::$version,
	$options['mode'],
) ).PHP_EOL );
?>
