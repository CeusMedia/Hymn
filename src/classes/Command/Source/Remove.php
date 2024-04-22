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
 *	@package		CeusMedia.Hymn.Command.Source
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Source
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Source_Remove extends Hymn_Command_Source_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags: dry, force, quiet, verbose
	 *	@access		public
	 *	@return		void
	 */
	public function run( ?Hymn_Tool_CLI_Arguments $arguments = NULL ): void
	{
		if( !( $arguments instanceof Hymn_Tool_CLI_Arguments ) )
			$arguments	= $this->client->arguments;
		$config		= $this->client->getConfig();
		if( !( $shelf = $this->getShelfByArgument( 0, $arguments ) ) )
			return;

		if( !$shelf->active && !$this->flags->force ){
			$this->client->outVerbose( 'Source "'.$shelf->id.'" is active and needs to be disabled, first.' );
			return;
		}

		if( $this->flags->dry ){
			if( !$this->flags->quiet )
				$this->out( 'Source "'.$shelf->id.'" would have been removed.' );
			return;
		}
//		unset( $config->sources[$shelf->id] );
		$json	= Hymn_Tool_ConfigFile::read( Hymn_Client::$fileName );
		unset( $json->sources[$shelf->id] );
		Hymn_Tool_ConfigFile::save( $json, Hymn_Client::$fileName );
		if( !$this->flags->quiet )
			$this->out( 'Source "'.$shelf->id.'" has been removed.' );
	}
}
