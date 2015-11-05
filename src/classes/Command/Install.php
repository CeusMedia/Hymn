<?php
class Hymn_Command_Install extends Hymn_Command_Abstract implements Hymn_Command_Interface{

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

		$moduleId	= $this->client->arguments->getArgument();
		if( $moduleId ){
			$module			= $library->getModule( $moduleId );
			if( $module ){
				$installType	= $this->client->getModuleInstallType( $moduleId, $this->installType );
				$relation->addModule( $module, $installType );
			}
		}
		else{
			foreach( $config->modules as $moduleId => $module ){
				if( preg_match( "/^@/", $moduleId ) )
					continue;
				if( !isset( $module->active ) || $module->active ){
					$module			= $library->getModule( $moduleId );
					$installType	= $this->client->getModuleInstallType( $moduleId, $this->installType );
					$relation->addModule( $module, $installType );
				}
			}
		}

		$installer	= new Hymn_Module_Installer( $this->client, $library, $this->quiet );
		$modules	= $relation->getOrder();
		foreach( $modules as $moduleId => $module ){
			$installed	= $library->listInstalledModules( $config->application->uri );
			if( array_key_exists( $module->id, $installed ) )
				Hymn_Client::out( "Module '".$module->id."' is already installed" );
			else{
				Hymn_Client::out( "Installing module '".$module->id."' ..." );
				$installType	= $this->client->getModuleInstallType( $moduleId, $this->installType );
				$installer->install( $module, $installType, $this->verbose );
			}
		}

/*		foreach( $config->modules as $moduleId => $module ){
			if( preg_match( "/^@/", $moduleId ) )
				continue;
			if( !isset( $module->active ) || $module->active ){
				$module			= $library->getModule( $moduleId );
				$installType	= $this->client->getModuleInstallType( $moduleId, $this->installType );
				$installer->install( $module, $installType, $this->verbose );
			}
		}*/
		if( isset( $config->database->import ) ){
			foreach( $config->database->import as $import ){
				if( file_exists( $import ) )
					$installer->executeSql( file_get_contents( $import ) );
			}
		}
	}
}
