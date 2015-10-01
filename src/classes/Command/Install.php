<?php
class Hymn_Command_Install extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected $installType	= "link";

	public function run( $arguments = array() ){
		$force		= FALSE;
		$verbose	= FALSE;
		$quiet		= FALSE;
		foreach( $arguments as $argument ){
			if( $argument == "-v" || $argument == "--verbose" )
				$verbose	= TRUE;
			else if( $argument == "-f" || $argument == "--force" )
				$force		= TRUE;
			else if( $argument == "-q" || $argument == "--quiet" )
				$quiet		= TRUE;
		}

		$config		= $this->client->getConfig();
		$library	= new Hymn_Module_Library();
		foreach( $config->sources as $sourceId => $source )
			$library->addShelf( $sourceId, $source->path );
		$installer	= new Hymn_Module_Installer( $this->client, $library, $quiet );
		foreach( $config->modules as $moduleId => $module ){
			if( preg_match( "/^@/", $moduleId ) )
				continue;
			if( !isset( $module->active ) || $module->active ){
				$module			= $library->getModule( $moduleId );
				$installType	= $this->client->getModuleInstallType( $moduleId, $this->installType );
				$installer->install( $module, $installType, $verbose );
			}
		}
		if( isset( $config->database->import ) ){
			foreach( $config->database->import as $import ){
				if( file_exists( $import ) )
					$installer->executeSql( file_get_contents( $import ) );
			}
		}
	}
}
