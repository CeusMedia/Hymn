<?php
class Hymn_Command_ModulesAvailable extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run( $arguments = array() ){
		$config		= $this->client->getConfig();
		$library	= new Hymn_Module_Library();
		foreach( $config->sources as $sourceId => $source )
			$library->addShelf( $sourceId, $source->path );

		Hymn_Client::out();
		if( !empty( $arguments[1] ) ){
			$modules	= $library->getModules( $arguments[1] );
			Hymn_Client::out( count( $modules )." modules available in module source '".$arguments[1]."':" );
			foreach( $modules as $moduleId => $module )
				Hymn_Client::out( "- ".$module->id );
		}
		else{
			$modules	= $library->getModules();
			Hymn_Client::out( count( $modules )." modules available:" );
			foreach( $modules as $moduleId => $module )
				Hymn_Client::out( "- ".$module->id );
		}
	}
}
