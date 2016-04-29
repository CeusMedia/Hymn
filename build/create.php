<?php
$rootPath		= dirname( __DIR__ );
$pharFileName	= 'hymn.phar';
$pharFilePath	= $rootPath.'/'.$pharFileName;
$mainFileName	= 'hymn.php';


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
$archive->buildFromDirectory( $rootPath.'/src/classes/', '$(.*)\.php$' );
$archive->stopBuffering();
?>
