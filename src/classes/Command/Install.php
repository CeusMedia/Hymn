<?php
class Hymn_Command_Install extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected $installType	= "link";
	protected $force		= FALSE;
	protected $verbose		= FALSE;
	protected $quiet		= FALSE;

	public function run( $arguments = array() ){
		foreach( $arguments as $argument ){
			if( $argument == "-v" || $argument == "--verbose" )
				$this->verbose	= TRUE;
			else if( $argument == "-f" || $argument == "--force" )
				$this->force		= TRUE;
			else if( $argument == "-q" || $argument == "--quiet" )
				$this->quiet		= TRUE;
		}

		$config		= $this->client->getConfig();
		$library	= new Hymn_Module_Library();
		foreach( $config->sources as $sourceId => $source )
			$library->addShelf( $sourceId, $source->path );
		$relation	= new Hymn_Module_Graph( $this->client, $library );
		foreach( $config->modules as $moduleId => $module ){
			if( preg_match( "/^@/", $moduleId ) )
				continue;
			if( !isset( $module->active ) || $module->active ){
				$module			= $library->getModule( $moduleId );
				$installType	= $this->client->getModuleInstallType( $moduleId, $this->installType );
				$relation->addModule( $module, $installType );
			}
		}

		$installer	= new Hymn_Module_Installer( $this->client, $library, $this->quiet );
		$modules	= $relation->getOrder();
		foreach( $modules as $moduleId => $module ){
			$installType	= $this->client->getModuleInstallType( $moduleId, $this->installType );
			$installer->install( $module, $installType, $this->verbose );
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
