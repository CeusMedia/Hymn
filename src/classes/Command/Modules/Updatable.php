<?php
/**
 *	@todo 		handle relations (new relations after update)
 */
class Hymn_Command_Modules_Updatable extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		$config		= $this->client->getConfig();
		$library	= new Hymn_Module_Library();
		foreach( $config->sources as $sourceId => $source )
			$library->addShelf( $sourceId, $source->path );
		$relation	= new Hymn_Module_Graph( $this->client, $library );

		$modules		= array();																	//  prepare list of modules to update
		$listInstalled	= $library->listInstalledModules( $config->application->uri );				//  get list of installed modules
		if( !$listInstalled )																		//  application has no installed modules
			return Hymn_Client::out( "No installed modules found" );

		$outdatedModules	= array();																//
		foreach( $listInstalled as $installedModule ){
			$source				= $installedModule->installSource;
			$availableModule	= $library->getModule( $installedModule->id, $source, FALSE );
			if( $availableModule ){
				if( version_compare( $availableModule->version, $installedModule->version, '>' ) ){
					$outdatedModules[$installedModule->id]	= (object) array(
						'id'		=> $installedModule->id,
						'installed'	=> $installedModule->version,
						'available'	=> $availableModule->version,
						'source'	=> $installedModule->installSource,
					);
				}
			}
		}

		foreach( $outdatedModules as $update ){
			$message	= "- %s (%s -> %s) ...";
			$message	= sprintf( $message, $update->id, $update->installed, $update->available );
			Hymn_Client::out( $message );
		}
	}
}
