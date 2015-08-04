<?php
class Hymn_Command_ModulesAvailable extends Hymn_Command_Abstract implements Hymn_Command_Interface{
	public function run( $arguments = array() ){
		$config		= $this->client->getConfig();
		$library	= new Hymn_Module_Library();
		foreach( $config->sources as $sourceId => $source )
			$library->addShelf( $sourceId, $source->path );

		if( !empty( $arguments[1] ) ){
			$modules	= $library->getModules( $arguments[1] );
			Hymn_Client::out( "Available modules of library shelf '".$arguments[1]."' (".count( $modules )."):" );
			foreach( $modules as $moduleId => $module )
				Hymn_Client::out( "- ".$module->id );
		}
		else{
			$modules	= $library->getModules();
			Hymn_Client::out( "Available modules (".count( $modules )."):" );
			foreach( $modules as $moduleId => $module )
				Hymn_Client::out( "- ".$module->id );
		}
	}
}
