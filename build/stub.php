<?php
try {
	Phar::mapPhar("[pharFileName]");
	include "phar://[pharFileName]/[mainFileName]";
} catch (PharException $e) {
	print( "Error: ".$e->getMessage().PHP_EOL );
//	die("Cannot initialize Phar");
}
__HALT_COMPILER();
