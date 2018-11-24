<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2018 Christian Würker (ceusmedia.de)
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Config_Get extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$key		= $this->client->arguments->getArgument( 0 );
		if( !strlen( trim( $key ) ) )
			throw new InvalidArgumentException( 'Missing first argument "key" is missing' );
		$config		= $this->loadConfig();
		$current	= $this->getCurrentValue( $config, $key );
		$this->client->out( $current );
	}

	/*  --  PROTECTED  --  */
	protected function getCurrentValue( $config, $key ){
		$parts	= explode( ".", $key );
		if( count( $parts ) === 3 ){
			if( !isset( $config->{$parts[0]} ) )
				$config->{$parts[0]}	= (object) array();
			if( !isset( $config->{$parts[0]}->{$parts[1]} ) )
				$config->{$parts[0]}->{$parts[1]}	= (object) array();
			if( !isset( $config->{$parts[0]}->{$parts[1]}->{$parts[2]} ) )
				$config->{$parts[0]}->{$parts[1]}->{$parts[2]}	= NULL;
			return $config->{$parts[0]}->{$parts[1]}->{$parts[2]};
		}
		else if( count( $parts ) === 2 ){
			if( !isset( $config->{$parts[0]} ) )
				$config->{$parts[0]}	= (object) array();
			if( !isset( $config->{$parts[0]}->{$parts[1]} ) )
				$config->{$parts[0]}->{$parts[1]}	= NULL;
			return $config->{$parts[0]}->{$parts[1]};
		}
		$this->client->outError( 'Invalid key - must be of syntax "path.(subpath.)key"', Hymn_Client::EXIT_ON_RUN );
	}

	protected function loadConfig(){
		$filePath	= Hymn_Client::$fileName;
		if( !file_exists( $filePath ) )
			throw new RuntimeException( 'File "'.$filePath.'" is missing' );
		$config	= json_decode( file_get_contents( $filePath ) );
		if( json_last_error() ){
			$message	= 'Configuration file "%s" is not valid JSON: %s';
			$this->client->outError(
				vsprintf( $message, array( $filePath, json_last_error_msg() ) ),
				Hymn_Client::EXIT_ON_RUN
			);
		}
		return $config;
	}
}
