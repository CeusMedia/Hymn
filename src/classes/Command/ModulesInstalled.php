<?php
class Hymn_Command_ModulesInstalled extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run( $arguments = array() ){
		$config		= $this->client->getConfig();
		$library	= new Hymn_Module_Library();
		$modules	= $library->listInstalledModules( $config->application->uri );
		ksort( $modules );
		Hymn_Client::out();
		Hymn_Client::out( count( $modules )." modules installed:" );
		foreach( $modules as $module ){
			Hymn_Client::out( "- ".$module->id );
		}
	}
}
