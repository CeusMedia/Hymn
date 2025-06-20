<?php
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
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
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
	public function run(): void
	{
//		$config		= $this->client->getConfig();
		$library	= $this->getLibrary();
		$moduleId	= $this->client->arguments->getArgument();
		$sourceId	= $this->client->arguments->getArgument( 1 );

		if( !strlen( trim( $moduleId ?? '' ) ) )
			$this->client->outError( 'No module ID given.', Hymn_Client::EXIT_ON_INPUT );
		if( !strlen( trim( $sourceId ?? '' ) ) )
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
			$sourcesWithModule	= $library->getAvailableModuleSources( $moduleId );
			if( count( $sourcesWithModule ) > 1 ){
				$message	= 'Module exists in several sources: %s. Please specify!';
				$message	= sprintf( $message, join( ', ', array_keys( $sourcesWithModule ) ) );
				$this->client->outError( $message, Hymn_Client::EXIT_ON_INPUT );
			}
		}

		$installTypes	= [0 => 'Copy', 1 => 'Link'];

		$availableModule	= $modulesAvailable[$moduleId];

		$frameworks	= [];
		foreach( $availableModule->frameworks as $frameworkIdentifier => $frameworkVersion )
			$frameworks[]	= $frameworkIdentifier.'@'.$frameworkVersion;
		$frameworks	= join( ' | ', $frameworks );

		$this->out( $availableModule->title );
		if( $availableModule->description )
			$this->out( $availableModule->description );
		$this->out( ' - Category:     '.$availableModule->category );
		$this->out( ' - Source:       '.$availableModule->sourceId );
		$this->out( ' - Version:      '.$availableModule->version->current );
		$this->out( ' - Frameworks:   '.$frameworks );

		if( NULL !== $availableModule->deprecation ){
			$deprecation	= $availableModule->deprecation;
			$this->out( ' - Deprecated:   with version '.$deprecation->version );
			if( strlen( trim( $deprecation->message ) ) > 0 )
				$this->out( '   - Message:    '.$deprecation->message );
			if( strlen( trim( $deprecation->url ) ) > 0 )
				$this->out( '   - New URL:    '.$deprecation->url );
		}

		if( array_key_exists( $moduleId, $modulesInstalled ) ){
			$installedModule	= $modulesInstalled[$moduleId];
			$this->out( ' - Installed:' );
			$this->out( '    - Version: '.$installedModule->version->current );
			$this->out( '    - Source:  '.$installedModule->install->source );
			$this->out( '    - Type:    '.$installTypes[$installedModule->install->type] );
			$this->out( '    - Date:    '.date( 'Y-m-d H:i:s', (int) $installedModule->install->date ) );
			$message	= ' - Updatable: no';
			if( version_compare( $availableModule->version->current, $installedModule->version->current, '>' ) ){
				$message	= ' - Updatable: yes, from %s to %s';
				$message	= sprintf( $message, $installedModule->version->current, $availableModule->version->current );
			}
			$this->out( $message );
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
