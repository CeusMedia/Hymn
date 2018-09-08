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
 *	@package		CeusMedia.Hymn.Command.Config.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Config.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Config_Module_Add extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: dry, force, quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$filename	= Hymn_Client::$fileName;
		$config		= json_decode( file_get_contents( $filename ) );

		$moduleName	= $this->client->arguments->getArgument( 0 );
		if( !strlen( trim( $moduleName ) ) )
			throw new InvalidArgumentException( 'First argument "module" is missing' );
		$moduleId	= str_replace( ":", "_", $moduleName );

		if( isset( $config->modules->{$moduleId} ) )
			throw new RuntimeException( 'Module "'.$moduleId.'" is already registered' );

		$availableModules	= $this->getAvailableModulesMap( $config );

		if( !array_key_exists( $moduleId, $availableModules ) )
			throw new RuntimeException( sprintf( 'Module "%s" is not available', $moduleId ) );

		$module			= $availableModules[$moduleId];
		$moduleObject	= (object) array();
		$msg			= 'Adding module "%s" (%s) from source "%s"';
		$this->client->out( sprintf( $msg, $module->id, $module->version, $module->sourceId ) );
		$moduleConfigValues	= array();
		foreach( $module->config as $moduleConfig ){
			$defaultValue	= $moduleConfig->value;
			$actualValue	= trim( $this->client->getInput(
				sprintf( 'Value for "%s:%s"', $module->id, $moduleConfig->key ),
				$moduleConfig->type,
				$moduleConfig->value,
				$moduleConfig->values,
				FALSE																				//  no break = inline question
			) );
			if( in_array( $moduleConfig->type, array( 'bool', 'boolean' ) ) ){
				$actualValue	= $actualValue ? 'yes' : 'no';
				$defaultValue	= 'no';
				if( in_array( $moduleConfig->value, array( 'yes', '1' ) ) )
					$defaultValue	= 'yes';
			}
			if( $actualValue !== $defaultValue )
				$moduleConfigValues[$moduleConfig->key]	= $actualValue;
		}
		if( count( $moduleConfigValues ) )
			$moduleObject->config	= $moduleConfigValues;
		$config->modules->{$module->id}	= $moduleObject;
		file_put_contents( $filename, json_encode( $config, JSON_PRETTY_PRINT ) );
		clearstatcache();
		$this->client->out( "Saved updated hymn file." );
	}
}
