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
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Module_Info extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run()
	{
		$config		= $this->client->getConfig();
		$library	= $this->getLibrary();
		$moduleId	= $this->client->arguments->getArgument( 0 );
		$sourceId	= $this->client->arguments->getArgument( 1 );

		if( !strlen( trim( $moduleId ) ) )
			$this->client->outError( 'No module ID given.', Hymn_Client::EXIT_ON_INPUT );
		if( !strlen( trim( $sourceId ) ) )
			$sourceId	= NULL;

		$modulesAvailable	= $library->getAvailableModules( $sourceId );
		$modulesInstalled	= $library->listInstalledModules( $sourceId );							//  get list of installed modules

		if( !array_key_exists( $moduleId, $modulesAvailable ) ){
			$message	= 'Module '.$moduleId.' not available.';
			if( $sourceId )
				$message	= 'Module '.$moduleId.' not available in source '.$sourceId.'.';
			$this->client->outError( $message, Hymn_Client::EXIT_ON_INPUT );
		}

		if( !$sourceId ){
			$shelvesWithModule	= $library->getAvailableModuleShelves( $moduleId );
			if( count( $shelvesWithModule ) > 1 ){
				$message	= 'Module exists in several sources: %s. Please specify!';
				$message	= sprintf( $message, join( ', ', array_keys( $shelvesWithModule ) ) );
				$this->client->outError( $message, Hymn_Client::EXIT_ON_INPUT );
			}
		}

		$installTypes	= [0 => 'Copy', 1 => 'Link'];

		$availableModule	= $modulesAvailable[$moduleId];

		$frameworks	= [];
		foreach( $availableModule->frameworks as $frameworkIdentifier => $frameworkVersion )
			$frameworks[]	= $frameworkIdentifier.'@'.$frameworkVersion;
		$frameworks	= join( ' | ', $frameworks );

		$this->client->out( $availableModule->title );
		if( $availableModule->description )
			$this->client->out( $availableModule->description );
		$this->client->out( ' - Category:     '.$availableModule->category );
		$this->client->out( ' - Source:       '.$availableModule->sourceId );
		$this->client->out( ' - Version:      '.$availableModule->version );
		$this->client->out( ' - Frameworks:   '.$frameworks );

		if( $availableModule->isDeprecated ){
			$deprecation	= (object) $availableModule->deprecation;
			$this->client->out( ' - Deprecated:   with version '.$deprecation->version );
			if( strlen( trim( $deprecation->message ) ) > 0 )
				$this->client->out( '   - Message:    '.$deprecation->message );
			if( strlen( trim( $deprecation->url ) ) > 0 )
				$this->client->out( '   - New URL:    '.$deprecation->url );
		}

		if( array_key_exists( $moduleId, $modulesInstalled ) ){
			$installedModule	= $modulesInstalled[$moduleId];
			$this->client->out( ' - Installed:' );
			$this->client->out( '    - Version: '.$installedModule->version );
			$this->client->out( '    - Source:  '.$installedModule->installSource );
			$this->client->out( '    - Type:    '.$installTypes[$installedModule->installType] );
			$this->client->out( '    - Date:    '.date( 'Y-m-d H:i:s', $installedModule->installDate ) );
			$message	= ' - Updatable: no';
			if( version_compare( $availableModule->version, $installedModule->version, '>' ) ){
				$message	= ' - Updatable: yes, from %s to %s';
				$message	= sprintf( $message, $installedModule->version, $availableModule->version );
			}
			$this->client->out( $message );
			$availableModule = $installedModule;
		}

		if( $this->flags->verbose ){
			$moduleInfo	= new Hymn_Module_Info( $this->client );
			$moduleInfo->showModuleVersions( $availableModule );
			$moduleInfo->showModuleFiles( $availableModule );
			$moduleInfo->showModuleConfig( $availableModule );
			$moduleInfo->showModuleRelations( $library, $availableModule );
			$moduleInfo->showModuleHook( $availableModule );
		}
	}
}
