<?php
class Hymn_Command_Graph extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected $installType	= "link";

	public function run(){
		if( !( file_exists( "config" ) && is_writable( "config" ) ) )
			return Hymn_Client::out( "Configuration folder is either not existing or not writable" );

		$force		= $this->client->arguments->getOption( 'force' );
		$verbose	= $this->client->arguments->getOption( 'verbose' );
		$quiet		= $this->client->arguments->getOption( 'quiet' );

		if( !$quiet && $verbose )
			Hymn_Client::out( "Loading all needed modules into graph…" );
		$config		= $this->client->getConfig();
		$library	= new Hymn_Module_Library();
		foreach( $config->sources as $sourceId => $source )
			$library->addShelf( $sourceId, $source->path );
		$relation	= new Hymn_Module_Graph( $this->client, $library, $quiet );
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
		$graph	= $relation->renderGraphFile( $targetFileGraph, $verbose );
//		if( !$quiet )
//			Hymn_Client::out( "Saved graph file to ".$targetFileGraph."." );

		$image	= $relation->renderGraphImage( $graph, $targetFileImage, $verbose );
//		if( !$quiet )
//			Hymn_Client::out( "Saved graph image to ".$targetFileImage."." );
	}
}
