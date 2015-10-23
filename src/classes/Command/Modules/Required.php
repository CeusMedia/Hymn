<?php
class Hymn_Command_Modules_Required extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		$modules	= (array) $this->client->getConfig()->modules;
		Hymn_Client::out( count( $modules )." modules required:" );
		foreach( $modules as $module ){
			Hymn_Client::out( "- ".$module->id );
		}
	}
}
