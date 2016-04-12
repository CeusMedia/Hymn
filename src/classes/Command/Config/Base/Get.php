<?php
class Hymn_Command_Config_Base_Get extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		$key	= $this->client->arguments->getArgument( 0 );
		if( !strlen( trim( $key ) ) )
			throw new InvalidArgumentException( 'Missing first argument "key" is missing' );
		$editor	= new Hymn_Tool_BaseConfigEditor( "config/config.ini" );

		if( !$editor->hasProperty( $key, FALSE ) )
			throw new InvalidArgumentException( 'Base config key "'.$key.'" is missing' );
		$current	= $editor->getProperty( $key );
		Hymn_Client::out( $current );
		clearstatcache();
	}
}
