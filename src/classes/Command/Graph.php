<?php
class Hymn_Command_Graph extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected $installType	= "link";
	protected $force		= FALSE;
	protected $verbose		= FALSE;
	protected $quiet		= FALSE;

	public function run( $arguments = array() ){
		if( !( file_exists( "config" ) && is_writable( "config" ) ) )
			return Hymn_Client::out( "Configuration folder is either not existing or not writable" );
		foreach( $arguments as $argument ){
			if( $argument == "-v" || $argument == "--verbose" )
				$this->verbose	= TRUE;
			else if( $argument == "-f" || $argument == "--force" )
				$this->force		= TRUE;
			else if( $argument == "-q" || $argument == "--quiet" )
				$this->quiet		= TRUE;
		}

		if( !$this->quiet && $this->verbose )
			Hymn_Client::out( "Loading all needed modules into graph…" );
		$config		= $this->client->getConfig();
		$library	= new Hymn_Module_Library();
		foreach( $config->sources as $sourceId => $source )
			$library->addShelf( $sourceId, $source->path );
		$relation	= new Hymn_Module_Graph( $this->client, $library, $this->quiet );
		foreach( $config->modules as $moduleId => $module ){
			if( preg_match( "/^@/", $moduleId ) )
				continue;
			if( !isset( $module->active ) || $module->active ){
				$module			= $library->getModule( $moduleId );
				$installType	= $this->client->getModuleInstallType( $moduleId, $this->installType );
				$relation->addModule( $module, $installType );
			}
		}

		$targetFileGraph	= "config/modules.graph";
		$targetFileImage	= "config/modules.graph.png";
		$graph	= $relation->renderGraphFile( $targetFileGraph, $this->verbose );
//		if( !$this->quiet )
//			Hymn_Client::out( "Saved graph file to ".$targetFileGraph."." );

		$image	= $relation->renderGraphImage( $graph, $targetFileImage, $this->verbose );
//		if( !$this->quiet )
//			Hymn_Client::out( "Saved graph image to ".$targetFileImage."." );
	}
}
