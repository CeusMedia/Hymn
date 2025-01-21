<?php /** @noinspection PhpUnused */
declare(strict_types=1);

/**
 *	...
 *
 *	Copyright (c) 2014-2025 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_Config_Set extends Hymn_Command_Config_Get implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: dry, force?, quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		$key		= trim( $this->client->arguments->getArgument() ?? '' );
		$value		= trim( $this->client->arguments->getArgument( 1 ) ?? '' );
		if( '' === $key ) {
			$this->client->outError(
				'Missing first argument "path" is missing',
				Hymn_Client::EXIT_ON_INPUT
			);
		}

//		$config		= $this->client->getConfig();
		$config		= Hymn_Tool_ConfigFile::read( Hymn_Client::$fileName );
		$current	= $this->getCurrentValue( $config, $key );

		if( '' === $value )
			$value	= trim( $this->ask( 'Value for "'.$key.'"', 'string', $current ) );

		if( preg_match( '/^".*"$/', $value ) )
			$value	= substr( $value, 1, -1 );
		if( in_array( $key, ['application.uri'], TRUE ) )											//  force trailing slash
			$value	= rtrim( $value, '/' ).'/';

		if( $current === $value ){
			$this->client->outVerbose( 'No change made' );
			return;
		}

		$this->setValue( $config, $key, $value );

		Hymn_Tool_ConfigFile::save( $config, Hymn_Client::$fileName );
		clearstatcache();
	}

	/**
	 *	@param		Hymn_Structure_Config	$config
	 *	@param		string					$key
	 *	@param		string					$value
	 *	@return		void
	 */
	protected function setValue( Hymn_Structure_Config $config, string $key, string $value ): void
	{
		$parts		= explode( '.', trim( $key, '.' ), 4 );
		$lastKey	= array_pop( $parts );
		$here		= $config;
		while( $parts ){
			$part	= array_shift( $parts );
			$this->outVeryVerbose('Part: '.$part );
			if( is_object( $here ) && isset( $here->$part ) ){
				$here	= & $here->$part;
				continue;
			}
			else if( is_array( $here ) && isset( $here[$part] ) ){
				$here	= & $here[$part];
				continue;
			}
			$this->outError( 'Invalid path given', Hymn_Client::EXIT_ON_INPUT );
		}
		if( is_object( $here ) && isset( $here->$lastKey ) ){
			$here->$lastKey	= $value;
			$this->outVeryVerbose( 'Set '.$lastKey.' on object '.( $part ?? '' ) );
		}
		else if( is_array( $here ) && isset( $here[$lastKey] ) ){
			$here[$lastKey]	= $value;
			$this->outVeryVerbose( 'Set '.$lastKey.' on array '.( $part ?? '' ) );
		}
		else
			$this->outError( 'Invalid path given: key "'.$lastKey.'" not found', Hymn_Client::EXIT_ON_INPUT );
	}
}
