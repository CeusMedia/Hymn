<?php
$rootPath		= dirname( __DIR__ );
$pharFileName	= 'hymn.phar';
$pharFilePath	= $rootPath.'/'.$pharFileName;
$mainFileName	= 'hymn.php';

$devMode	= isset( $argv[1] ) && $argv[1] === "dev";

$archive		= new Phar(
	$pharFilePath,
	FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME,
	$pharFileName
);
$archive->startBuffering();
$archive->setStub( '#!/usr/bin/env php
<?php
try {
	Phar::mapPhar("'.$pharFileName.'");
	include "phar://'.$pharFileName.'/'.$mainFileName.'";
} catch (PharException $e) {
	echo $e->getMessage();
	die("Cannot initialize Phar");
}
__HALT_COMPILER(); ?>' );
$archive->addFromString( $mainFileName, file_get_contents( __DIR__.'/'.$mainFileName ) );
$archive->addFile( $rootPath.'/src/locales/en/help/default.txt', 'locales/en/help/default.txt' );
$archive->addFile( $rootPath.'/src/locales/en/help/reflect-options.txt', 'locales/en/help/reflect-options.txt' );
$archive->addFile( $rootPath.'/src/templates/Makefile', 'templates/Makefile' );
$archive->addFile( $rootPath.'/src/templates/phpunit.xml', 'templates/phpunit.xml' );
$archive->addFile( $rootPath.'/src/templates/test_bootstrap.php', 'templates/test_bootstrap.php' );

shell_exec( "cp -r ".$rootPath."/src/classes ".$rootPath."/build/" );
$directory	= new RecursiveDirectoryIterator( $rootPath."/build/classes", RecursiveDirectoryIterator::SKIP_DOTS );
$iterator	= new RecursiveIteratorIterator( $directory, RecursiveIteratorIterator::CHILD_FIRST );
foreach( $iterator as $entry ){
	if( !$entry->isDir() ){
		$content	= php_strip_whitespace( $entry->getPathname() );
		if( $devMode )
			$content	= file_get_contents( $entry->getPathname() );
		file_put_contents( $entry->getPathname(), $content );
	}
}

$archive->buildFromDirectory( $rootPath.'/build/classes/', '$(.*)\.php$' );
$archive->compressFiles( Phar::GZ );
$archive->stopBuffering();
shell_exec( "rm -rf ".$rootPath."/build/classes" );
?>
