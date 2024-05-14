<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2024 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_Config_Get extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		$key		= $this->client->arguments->getArgument() ?? '';
		if( !strlen( trim( $key ) ) )
			throw new InvalidArgumentException( 'Missing first argument "key" is missing' );
		$current	= $this->getCurrentValue( $this->client->getConfig(), $key );
		$this->out( $current );
	}

	/*  --  PROTECTED  --  */
	protected function getCurrentValue( Hymn_Structure_Config $config, string $key ): string|bool|int|float|NULL
	{
		$parts		= explode( '.', trim( $key, '.' ), 4 );
		$lastKey	= array_pop( $parts );
		$here	= $config;
		while( $parts ){
			$part	= array_shift( $parts );
			$this->outVeryVerbose('Part: '.$part );
			if( is_object( $here ) && isset( $here->$part ) ){
				$here	= $here->$part;
				continue;
			}
			else if( is_array( $here ) && isset( $here[$part] ) ){
				$here	= $here[$part];
				continue;
			}
			$this->outError( 'Invalid key - must be of syntax "path.(subpath.)key"', Hymn_Client::EXIT_ON_RUN );
		}

		if( is_object( $here ) && isset( $here->$lastKey ) )
			return $here->$lastKey;
		else if( is_array( $here ) && isset( $here[$lastKey] ) )
			return $here[$lastKey];

		$this->outError( 'Invalid path given: key "'.$lastKey.'" not found', Hymn_Client::EXIT_ON_INPUT );
		return NULL;
	}

	/**
	 * @deprecated
	 */
	protected function getCurrentValueTheOldWay( Hymn_Structure_Config $config, string $key ): string|bool|int|float|NULL
	{
		$parts	= explode( '.', trim( $key, '.' ) );

		if( count( $parts ) === 3 ){
			if( !isset( $config->{$parts[0]} ) )
				$config->{$parts[0]}	= (object) [];
			if( !isset( $config->{$parts[0]}->{$parts[1]} ) )
				$config->{$parts[0]}->{$parts[1]}	= (object) [];
			if( !isset( $config->{$parts[0]}->{$parts[1]}->{$parts[2]} ) )
				$config->{$parts[0]}->{$parts[1]}->{$parts[2]}	= NULL;
			return $config->{$parts[0]}->{$parts[1]}->{$parts[2]};
		}
		else if( count( $parts ) === 2 ){
			if( !isset( $config->{$parts[0]} ) )
				$config->{$parts[0]}	= (object) [];
			if( !isset( $config->{$parts[0]}->{$parts[1]} ) )
				$config->{$parts[0]}->{$parts[1]}	= NULL;
			return $config->{$parts[0]}->{$parts[1]};
		}
		$this->client->outError( 'Invalid key - must be of syntax "path.(subpath.)key"', Hymn_Client::EXIT_ON_RUN );
		return NULL;
	}
}
