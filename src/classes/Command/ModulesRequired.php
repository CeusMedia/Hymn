<?php
class Hymn_Command_ModulesRequired extends Hymn_Command_Abstract implements Hymn_Command_Interface{
	public function run( $arguments = array() ){
		print_m( $this->client->getConfig()->modules );
	}
}
