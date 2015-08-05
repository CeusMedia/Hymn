#!/usr/bin/env php
<?php
require_once __DIR__.'/classes/Client.php';
require_once __DIR__.'/classes/Module/Library.php';
require_once __DIR__.'/classes/Module/Installer.php';
require_once __DIR__.'/classes/Module/Reader.php';
require_once __DIR__.'/classes/Command/Abstract.php';
require_once __DIR__.'/classes/Command/Interface.php';
require_once __DIR__.'/classes/Command/Configure.php';
require_once __DIR__.'/classes/Command/DatabaseDump.php';
require_once __DIR__.'/classes/Command/Create.php';
require_once __DIR__.'/classes/Command/DatabaseTest.php';
require_once __DIR__.'/classes/Command/Default.php';
require_once __DIR__.'/classes/Command/Help.php';
require_once __DIR__.'/classes/Command/Info.php';
require_once __DIR__.'/classes/Command/Sources.php';
require_once __DIR__.'/classes/Command/ModulesRequired.php';
require_once __DIR__.'/classes/Command/ModulesInstalled.php';
require_once __DIR__.'/classes/Command/ModulesAvailable.php';
require_once __DIR__.'/classes/Command/Install.php';
new Hymn_Client( array_slice( $argv, 1 ) );
?>
