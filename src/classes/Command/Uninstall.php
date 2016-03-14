<?php
class Hymn_Command_Uninstall extends Hymn_Command_Abstract implements Hymn_Command_Interface{

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

		$moduleId		= trim( $this->client->arguments->getArgument() );
		$listInstalled	= $library->listInstalledModules( $config->application->uri );
		$isInstalled	= array_key_exists( $moduleId, $listInstalled );
		if( !$moduleId )
			Hymn_Client::out( "No module id given" );
		else if( !$isInstalled )
			Hymn_Client::out( "Module '".$moduleId."' is not installed" );
		else{
			$module		= $listInstalled[$moduleId];
			$neededBy	= array();
			foreach( $listInstalled as $installedModuleId => $installedModule )
				if( in_array( $moduleId, $installedModule->relations->needs ) )
					$neededBy[]	= $installedModuleId;
			if( $neededBy && !$this->force ) {
				$list	= implode( ', ', $neededBy );
				$msg	= "Module '%s' is needed by %d other modules (%s)";
				Hymn_Client::out( sprintf( $msg, $module->id, count( $neededBy ), $list ) );
			}
			else{
				$module->path	= 'not_relevant/';
				$installer	= new Hymn_Module_Installer( $this->client, $library, $this->quiet );
				$installer->uninstall( $module, $this->verbose );
			}
		}
/*
		if( $module ){
			Hymn_Client::out( "Installing module '".$module->id."' ..." );
			$installType	= $this->client->getModuleInstallType( $module->id, $this->installType );
			$installer->install( $module, $installType, $this->verbose );
		}

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
				$installer->install( $module, $installType, $this->verbose );
			}
		}
*/
/*		foreach( $config->modules as $moduleId => $module ){
			if( preg_match( "/^@/", $moduleId ) )
				continue;
			if( !isset( $module->active ) || $module->active ){
				$module			= $library->getModule( $moduleId );
				$installType	= $this->client->getModuleInstallType( $moduleId, $this->installType );
				$installer->install( $module, $installType, $this->verbose );
			}
		}*/
	}
}
