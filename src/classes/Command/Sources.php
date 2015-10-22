<?php
class Hymn_Command_Sources extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		$config		= $this->client->getConfig();
		$library	= new Hymn_Module_Library();
		$sources	= (array) $config->sources;
		foreach( $sources as $sourceId => $source )
			$library->addShelf( $sourceId, $source->path );

		Hymn_Client::out( count( $sources )." module sources:" );
		foreach( $library->getShelves() as $shelf )
			Hymn_Client::out( "- ".$shelf->id." -> ".$shelf->path );
	}
}
