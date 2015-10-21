<?php
class Hymn_Command_ConfigGet extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run( $arguments = array() ){
		array_shift( $arguments );
		$filename	= Hymn_Client::$fileName;
		if( !file_exists( $filename ) )
			throw new RuntimeException( 'File "'.$filename.'" is missing' );
		$config	= json_decode( file_get_contents( $filename ) );
		if( is_null( $config ) )
			throw new RuntimeException( 'Configuration file "'.$filename.'" is not valid JSON' );

		if( !isset( $arguments[0] ) )
			throw new InvalidArgumentsException( 'Missing first argument "key" is missing' );

		$key	= $arguments[0];
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
		Hymn_Client::out( $current );
	}
}
