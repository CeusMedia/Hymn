<?php
class Hymn_Command_Config_Base_Set extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		$key	= $this->client->arguments->getArgument( 0 );
		$value	= $this->client->arguments->getArgument( 1 );
		if( !strlen( trim( $key ) ) )
			throw new InvalidArgumentException( 'Missing first argument "key" is missing' );

		$editor	= new Hymn_Tool_BaseConfigEditor( "config/config.ini" );
		if( !$editor->hasProperty( $key, FALSE ) )
			throw new InvalidArgumentException( 'Base config key "'.$key.'" is missing' );
		$current	= $editor->getProperty( $key, FALSE );

		if( !strlen( trim( $value ) ) )
			$value	= trim( Hymn_Client::getInput( "Value for '".$key."'", $current, array(), FALSE ) );

		$editor->setProperty( $key, $value );
		clearstatcache();
	}
}
