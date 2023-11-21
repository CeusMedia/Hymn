<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2022 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Command.App.Base.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App.Base.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_App_Base_Config_Set extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: dry, force, quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run()
	{
		$key		= $this->client->arguments->getArgument();
		$value		= $this->client->arguments->getArgument( 1 );
		$pathConfig	= $this->client->getConfigPath();

		if( !strlen( trim( $key ) ) )
			throw new InvalidArgumentException( 'Missing first argument "key" is missing' );

		$editor	= new Hymn_Tool_BaseConfigEditor( $pathConfig."config.ini" );
		if( !$this->flags->force && !$editor->hasProperty( $key, FALSE ) )
			throw new InvalidArgumentException( 'Base config key "'.$key.'" is missing' );
		$current	= $editor->getProperty( $key, FALSE );

		if( !strlen( trim( $value ) ) ){
			$question	= new Hymn_Tool_CLI_Question(
				$this->client,
				"Value for '".$key."'",
				'string',
				$current,
				[],
				FALSE																				//  no break = inline question
			);
			$value	= trim( $question->ask() );
		}
		if( !$this->flags->dry ){
			if( $editor->hasProperty() )
				$editor->setProperty( $key, $value );
			else
				$editor->addProperty( $key, $value );
			clearstatcache();
		}
		$this->client->outVerbose( 'Base config key "'.$key.'" set to "'.$value.'"' );
	}
}
