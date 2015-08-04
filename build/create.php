<?php
$p = new Phar(
    __DIR__.'/../hymn.phar',
    FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME,
    'hymn.phar'
);
$p->startBuffering();
$p->setStub('#!/usr/bin/env php
<?php
try {
    Phar::mapPhar("hymn.phar");
    include "phar://hymn.phar/hymn.php";
} catch (PharException $e) {
    echo $e->getMessage();
    die("Cannot initialize Phar");
}
__HALT_COMPILER(); ?>');
$p['hymn.php'] = file_get_contents(__DIR__.'/hymn.php');
$p->buildFromDirectory(__DIR__.'/../src/classes/', '$(.*)\.php$');
$p->stopBuffering();
?>
