<?php
require_once 'phar://hymn.phar/Client.php';
require_once 'phar://hymn.phar/Module/Library.php';
require_once 'phar://hymn.phar/Module/Installer.php';
require_once 'phar://hymn.phar/Command/Abstract.php';
require_once 'phar://hymn.phar/Command/Interface.php';
require_once 'phar://hymn.phar/Command/Configure.php';
require_once 'phar://hymn.phar/Command/Create.php';
require_once 'phar://hymn.phar/Command/DatabaseDump.php';
require_once 'phar://hymn.phar/Command/DatabaseTest.php';
require_once 'phar://hymn.phar/Command/Default.php';
require_once 'phar://hymn.phar/Command/Help.php';
require_once 'phar://hymn.phar/Command/Info.php';
require_once 'phar://hymn.phar/Command/Shelves.php';
require_once 'phar://hymn.phar/Command/ModulesRequired.php';
require_once 'phar://hymn.phar/Command/ModulesInstalled.php';
require_once 'phar://hymn.phar/Command/ModulesAvailable.php';
require_once 'phar://hymn.phar/Command/Install.php';
new Hymn_Client( array_slice( $argv, 1 ) );
?>
