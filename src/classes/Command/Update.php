<?php
/**
 *	@todo 		handle relations (new relations after update)
 */
class Hymn_Command_Update extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected $installType	= "link";
	protected $force		= FALSE;
	protected $verbose		= FALSE;
	protected $quiet		= FALSE;

	public function run(){
		$this->force	= $this->client->arguments->getOption( 'force' );
		$this->verbose	= $this->client->arguments->getOption( 'verbose' );
		$this->quiet	= $this->client->arguments->getOption( 'quiet' );

		$config		= $this->client->getConfig();
		$library	= new Hymn_Module_Library();
		foreach( $config->sources as $sourceId => $source )
			$library->addShelf( $sourceId, $source->path );
		$relation	= new Hymn_Module_Graph( $this->client, $library );

		$modules		= array();
		$moduleId		= trim( $this->client->arguments->getArgument() );
		$listInstalled	= $library->listInstalledModules( $config->application->uri );
		if( $moduleId ){
			if( !array_key_exists( $moduleId, $listInstalled ) )
				return Hymn_Client::out( "Module '".$moduleId."' is not installed and cannot be updated" );
			$modules[]	= $moduleId;
		}
		else{
			foreach( $config->modules as $moduleId => $module ){
				if( preg_match( "/^@/", $moduleId ) )
					continue;
				if( !isset( $module->active ) || $module->active )
					$modules[]	= $moduleId;
			}
		}
		if( !$modules )
			return Hymn_Client::out( "No installed modules found" );


		Hymn_Client::out( "## Updating modules not yet implemented - dry run for now ##" );

		foreach( $modules as $moduleId ){
			$module			= $library->getModule( $moduleId );
			$installType	= $this->client->getModuleInstallType( $moduleId, $this->installType );
/*			$relation->addModule( $module, $installType );
*/
			Hymn_Client::out( "Updating module '".$module->id."' ..." );
//			$installer->update( $module, $installType, $this->verbose );
		}

/*		$installer	= new Hymn_Module_Installer( $this->client, $library, $this->quiet );
		$modules	= $relation->getOrder();
		foreach( $modules as $module ){
			$listInstalled	= $library->listInstalledModules( $config->application->uri );
			$isInstalled	= array_key_exists( $module->id, $listInstalled );
			$isCalledModule	= $moduleId && $moduleId == $module->id;
			$isForced		= $this->force && ( $isCalledModule || !$moduleId );
			if( $isInstalled && !$isForced )
				Hymn_Client::out( "Module '".$module->id."' is already installed" );
			else{
				Hymn_Client::out( "Installing module '".$module->id."' ..." );
				$installType	= $this->client->getModuleInstallType( $module->id, $this->installType );
				$installer->update( $module, $installType, $this->verbose );
			}
		}
*/
	}
}
