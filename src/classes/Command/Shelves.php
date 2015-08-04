<?php
class Hymn_Command_Shelves extends Hymn_Command_Abstract implements Hymn_Command_Interface{
	public function run( $arguments = array() ){
		$config		= $this->client->getConfig();
		$library	= new Hymn_Module_Library();
		foreach( $config->sources as $sourceId => $source )
			$library->addShelf( $sourceId, $source->path );
		Hymn_Client::out();
		Hymn_Client::out( "Library shelves:" );
		foreach( $library->getShelves() as $shelf )
			Hymn_Client::out( "- ".$shelf->id." -> ".$shelf->path );
	}
}
