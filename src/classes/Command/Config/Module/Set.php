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
 *	@package		CeusMedia.Hymn.Command.Config.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Config.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Config_Module_Set extends Hymn_Command_Abstract implements Hymn_Command_Interface
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
		$filename   = Hymn_Client::$fileName;
		$config		= $this->client->getConfig();
		$key		= $this->client->arguments->getArgument();
		if( !strlen( trim( $key ) ) )
			throw new InvalidArgumentException( 'First argument "key" is missing' );
		$parts		= explode( ".", $key );
		$moduleId	= array_shift( $parts );
		if( !$parts )
			throw new InvalidArgumentException( 'Invalid key - must be of syntax "Module_Name.(section.)key"' );
		$configKey	= join( ".", $parts );
		$value	= $this->client->arguments->getArgument( 1 );

		$availableModules	= $this->getAvailableModulesMap();

		if( !isset( $config->modules->{$moduleId} ) )
			$config->modules->{$moduleId}	= (object) [];
		if( !isset( $config->modules->{$moduleId}->config ) )
			$config->modules->{$moduleId}->config	= (object) [];
		if( !isset( $config->modules->{$moduleId}->config->{$configKey} ) )
			$config->modules->{$moduleId}->config->{$configKey}	= NULL;

		$current	= $config->modules->{$moduleId}->config->{$configKey};

		$configType		= 'string';
		$configDefault	= NULL;
		$configValues	= [];
		if( array_key_exists( $moduleId, $availableModules ) ){
			$moduleConfig	= $availableModules[$moduleId]->config;
			if( isset( $moduleConfig[$configKey] ) ){
				$configType 	= $moduleConfig[$configKey]->type;
				$configDefault	= $moduleConfig[$configKey]->value;
				$configValues	= $moduleConfig[$configKey]->values;
			}
		}

		if( !strlen( trim( $value ) ) ){
			$question	= new Hymn_Tool_CLI_Question(
				$this->client,
				'Value for "'.$moduleId.':'.$configKey.'"',
				$configType,
				$configDefault,
				$configValues,
				FALSE																				//  no break = inline question
			);
			$value	= trim( $question->ask() );
		}
		if( preg_match( '/^".*"$/', $value ) )
			$value	= substr( $value, 1, -1 );

		if( $current === $value )
			throw new RuntimeException( 'No change made' );
		$config->modules->{$moduleId}->config->{$configKey}	= $value;
		file_put_contents( $filename, json_encode( $config, JSON_PRETTY_PRINT ) );
		clearstatcache();
	}
}
