<?php
class Hymn_Command_Version extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		Hymn_Client::out( Hymn_Client::$version );
	}
}
