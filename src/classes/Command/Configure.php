<?php
class Hymn_Command_Configure extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		Hymn_Client::out();
		Hymn_Client::out( "Deprecated: Please use 'hymn config-set' or 'hymn config-get' instead!" );
		Hymn_Client::out();

		$filename	= Hymn_Client::$fileName;
		if( !file_exists( $filename ) )
			throw new RuntimeException( 'File "'.$filename.'" is missing' );
		$config	= json_decode( file_get_contents( $filename ) );
		if( is_null( $config ) )
			throw new RuntimeException( 'Configuration file "'.$filename.'" is not valid JSON' );

		$key	= $this->client->arguments->getArgument( 0 );
		$value	= $this->client->arguments->getArgument( 1 );

		if( !strlen( trim( $key ) ) )
			throw new InvalidArgumentException( 'Missing first argument "key" is missing' );

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
		if( strlen( trim( $value ) ) ){
			if( $current === $value )
				throw new RuntimeException( 'No change made' );
			if( count( $parts ) === 3 )
				$config->{$parts[0]}->{$parts[1]}->{$parts[2]}	= $value;
			else if( count( $parts ) === 2 )
				$config->{$parts[0]}->{$parts[1]}	= $value;
			file_put_contents( $filename, json_encode( $config, JSON_PRETTY_PRINT ) );
//			Hymn_Client::out( "Saved." );
		}
		else{
			Hymn_Client::out( $current );
//			print "Current value: ".$current . PHP_EOL;
		}
	}
}
