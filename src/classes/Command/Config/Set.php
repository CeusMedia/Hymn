<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2019 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Config_Set extends Hymn_Command_Config_Get implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: dry, force?, quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$key		= $this->client->arguments->getArgument( 0 );
		$value		= $this->client->arguments->getArgument( 1 );
		if( !strlen( trim( $key ) ) )
			return $this->client->outError(
				'Missing first argument "key" is missing',
				Hymn_Client::EXIT_ON_INPUT
			);

		$config		= $this->loadConfig();
		$current	= $this->getCurrentValue( $config, $key );

		if( !strlen( trim( $value ) ) )
			$value	= trim( $this->ask( 'Value for "'.$key.'"', 'string', $current ) );
		if( preg_match( '/^".*"$/', $value ) )
			$value	= substr( $value, 1, -1 );
		if( $current === $value )
			return $this->client->outVerbose( 'No change made' );

		$parts	= explode( '.', $key );
		if( count( $parts ) === 3 )
			$config->{$parts[0]}->{$parts[1]}->{$parts[2]}	= $value;
		else if( count( $parts ) === 2 )
			$config->{$parts[0]}->{$parts[1]}	= $value;

		$filePath	= Hymn_Client::$fileName;
		file_put_contents( $filePath, json_encode( $config, JSON_PRETTY_PRINT ) );
		clearstatcache();
	}
}
