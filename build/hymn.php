<?php
require_once 'phar://hymn.phar/Loader.php';										//  laod class loader
require_once 'phar://hymn.phar/Command/Interface.php';							//  preload command interface
//require_once 'phar://hymn.phar/Command/Abstract.php';							//  preload abtract command class

new Hymn_Loader();																//  load hymn classes
new Hymn_Client( array_slice( $argv, 1 ) );										//  start hymn client
?>
