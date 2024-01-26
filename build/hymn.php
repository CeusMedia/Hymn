<?php
require_once 'phar://hymn.phar/Loader.php';										//  load class loader
require_once 'phar://hymn.phar/Command/Interface.php';							//  preload command interface
//require_once 'phar://hymn.phar/Command/Abstract.php';							//  preload abstract command class

$arguments	= array_slice( $argv, 1 );
new Hymn_Loader();																//  load hymn classes
new Hymn_Client( $arguments );													//  start hymn client
