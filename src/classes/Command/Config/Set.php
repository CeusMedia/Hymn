<?php
class Hymn_Command_Config_Set extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		$filename	= Hymn_Client::$fileName;
		if( !file_exists( $filename ) )
			throw new RuntimeException( 'File "'.$filename.'" is missing' );
		$config	= json_decode( file_get_contents( $filename ) );
		if( is_null( $config ) )
			throw new RuntimeException( 'Configuration file "'.$filename.'" is not valid JSON' );

		$key	= $this->client->arguments->getArgument( 0 );
		if( !strlen( trim( $key ) ) )
			throw new InvalidArgumentException( 'Missing first argument "key" is missing' );

		$key	= $this->client->arguments->getArgument( 0 );
		$value	= $this->client->arguments->getArgument( 1 );

		$parts	= explode( ".", $key );
		if( count( $parts ) === 3 ){
			if( !isset( $config->{$parts[0]} ) )
				$config->{$parts[0]}	= (object) array();
			if( !isset( $config->{$parts[0]}->{$parts[1]} ) )
				$config->{$parts[0]}->{$parts[1]}	= (object) array();
			if( !isset( $config->{$parts[0]}->{$parts[1]}->{$parts[2]} ) )
				$config->{$parts[0]}->{$parts[1]}->{$parts[2]}	= NULL;
			$current	= $config->{$parts[0]}->{$parts[1]}->{$parts[2]};
		}
		else if( count( $parts ) === 2 ){
			if( !isset( $config->{$parts[0]} ) )
				$config->{$parts[0]}	= (object) array();
			if( !isset( $config->{$parts[0]}->{$parts[1]} ) )
				$config->{$parts[0]}->{$parts[1]}	= NULL;
			$current	= $config->{$parts[0]}->{$parts[1]};
		}
		else
			throw new InvalidArgumentException( 'Invalid key - must be of syntax "path.(subpath.)key"' );

		if( !strlen( trim( $value ) ) )
			$value	= trim( Hymn_Client::getInput( "Value for '".$key."'", $current, array(), FALSE ) );
		if( preg_match( '/^".*"$/', $value ) )
			$value	= substr( $value, 1, -1 );

//		if( $current === $value )
//			throw new RuntimeException( 'No change made' );
		if( count( $parts ) === 3 )
			$config->{$parts[0]}->{$parts[1]}->{$parts[2]}	= $value;
		else if( count( $parts ) === 2 )
			$config->{$parts[0]}->{$parts[1]}	= $value;
		file_put_contents( $filename, json_encode( $config, JSON_PRETTY_PRINT ) );
		clearstatcache();
	}
}
