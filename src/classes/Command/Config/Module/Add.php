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
 *	@package		CeusMedia.Hymn.Command.Config.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Config.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_Config_Module_Add extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: dry, force, quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		$filename	= Hymn_Client::$fileName;
		$config		= Hymn_Tool_ConfigFile::read( $filename );

		$moduleName	= $this->client->arguments->getArgument();
		if( !strlen( trim( $moduleName ) ) )
			throw new InvalidArgumentException( 'First argument "module" is missing' );
		$moduleId	= str_replace( ":", "_", $moduleName );

		if( isset( $config->modules[$moduleId] ) )
			throw new RuntimeException( 'Module "'.$moduleId.'" is already registered' );

		$availableModules	= $this->getAvailableModulesMap();

		if( !array_key_exists( $moduleId, $availableModules ) )
			throw new RuntimeException( sprintf( 'Module "%s" is not available', $moduleId ) );

		$module			= $availableModules[$moduleId];
		$moduleObject	= (object) [];
		$msg			= 'Adding module "%s" (%s) from source "%s"';
		$this->out( sprintf( $msg, $module->id, $module->version->current, $module->sourceId ) );
		$moduleConfigValues	= [];
		foreach( $module->config as $moduleConfig ){
			$defaultValue	= $moduleConfig->value;
			$actualValue	= trim( Hymn_Tool_CLI_Question::getInstance(
				$this->client,
				sprintf( 'Value for "%s:%s"', $module->id, $moduleConfig->key ),
				$moduleConfig->type,
				$moduleConfig->value,
				$moduleConfig->values,
				FALSE																				//  no break = inline question
			)->ask() );
			if( in_array( $moduleConfig->type, ['bool', 'boolean'] ) ){
				$actualValue	= $actualValue ? 'yes' : 'no';
				$defaultValue	= 'no';
				if( in_array( $moduleConfig->value, ['yes', '1'] ) )
					$defaultValue	= 'yes';
			}
			if( $actualValue !== $defaultValue )
				$moduleConfigValues[$moduleConfig->key]	= $actualValue;
		}
		if( count( $moduleConfigValues ) )
			$moduleObject->config	= $moduleConfigValues;
		$config->modules[$module->id]	= $moduleObject;

		Hymn_Tool_ConfigFile::save( $config, $filename );
		clearstatcache();
		$this->out( "Saved updated hymn file." );
	}
}
