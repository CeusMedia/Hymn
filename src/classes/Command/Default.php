<?php
class Hymn_Command_Default extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run( $arguments = array() ){
//		Hymn_Client::out();
		Hymn_Client::out( "Arguments:" );
		foreach( $arguments as $nr => $arg )
		Hymn_Client::out( " ".$nr.". ".$arg );
	}
}
