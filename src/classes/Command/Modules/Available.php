<?php
class Hymn_Command_Modules_Available extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		$config		= $this->client->getConfig();
		$library	= new Hymn_Module_Library();
		foreach( $config->sources as $sourceId => $source )
			$library->addShelf( $sourceId, $source->path );

		$shelfId	= $this->client->arguments->getArgument( 0 );

		if( $shelfId ){
			$modules	= $library->getModules( $shelfId );
			Hymn_Client::out( count( $modules )." modules available in module source '".$shelfId."':" );
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
