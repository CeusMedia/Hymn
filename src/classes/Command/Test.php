<?php
class Hymn_Command_Test extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		Hymn_Test::checkPhpClasses( "./", FALSE, !FALSE );
		if( file_exists( "classes" ) )
			Hymn_Test::checkPhpClasses( "./classes", TRUE, !FALSE );
		if( file_exists( "templates" ) )
			Hymn_Test::checkPhpClasses( "./templates", TRUE, !FALSE );
	}
}
