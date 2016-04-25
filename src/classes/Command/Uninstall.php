<?php
class Hymn_Command_Uninstall extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected $installType	= "link";
	protected $force		= FALSE;
	protected $verbose		= FALSE;
	protected $quiet		= FALSE;

	public function run(){
		$this->dry		= $this->client->arguments->getOption( 'dry' );
		$this->force	= $this->client->arguments->getOption( 'force' );
		$this->quiet	= $this->client->arguments->getOption( 'quiet' );
		$this->verbose	= $this->client->arguments->getOption( 'verbose' );

		if( $this->dry )
			Hymn_Client::out( "## DRY RUN: Simulated actions - no changes will take place." );

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
				$installer->uninstall( $module, $this->verbose, $this->dry );
			}
		}
	}
}
