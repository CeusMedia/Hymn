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
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Module_Info extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$config		= $this->client->getConfig();
		$moduleId	= $this->client->arguments->getArgument( 0 );
		$sourceId	= $this->client->arguments->getArgument( 1 );

		if( !strlen( trim( $moduleId ) ) )
			$this->client->outError( 'No module ID given.', Hymn_Client::EXIT_ON_INPUT );
		if( !strlen( trim( $sourceId ) ) )
			$sourceId	= NULL;

		$modulesAvailable	= $this->getLibrary()->getModules( $sourceId );
		$modulesInstalled	= $this->getLibrary()->listInstalledModules( $sourceId );							//  get list of installed modules

		if( !array_key_exists( $moduleId, $modulesAvailable ) ){
			$message	= 'Module '.$moduleId.' not available.';
			if( $sourceId )
				$message	= 'Module '.$moduleId.' not available in source '.$sourceId.'.';
			$this->client->outError( $message, Hymn_Client::EXIT_ON_INPUT );
		}

		if( !$sourceId ){
			$shelvesWithModule	= $this->getLibrary()->getModuleShelves( $moduleId );
			if( count( $shelvesWithModule ) > 1 ){
				$message	= 'Module exists in several sources: %s. Please specify!';
				$message	= sprintf( $message, join( ', ', array_keys( $shelvesWithModule ) ) );
				$this->client->outError( $message, Hymn_Client::EXIT_ON_INPUT );
			}
		}

		$installTypes	= array( 0 => 'Copy', 1 => 'Link' );

		$availableModule	= $modulesAvailable[$moduleId];

		$frameworks	= array();
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
			$this->showModuleVersions( $availableModule );
			$this->showModuleFiles( $availableModule );
			$this->showModuleConfig( $availableModule );
			$this->showModuleRelations( $availableModule );
			$this->showModuleHook( $availableModule );
		}
	}

	protected function showModuleConfig( $module ){
		if( !count( $module->config ) )
			return;
		$this->client->out( ' - Configuration: ' );
		foreach( $module->config as $item ){
			$this->client->out( '    - '.$item->key.': '.trim( $item->title ) );
			$this->client->out( '       - Type:      '.$item->type );
			$this->client->out( '       - Value:     '.$item->value );
			if( $item->values )
				$this->client->out( '       - Values:    '.join( ', ', $item->values ) );
			$this->client->out( '       - Mandatory: '.( $item->mandatory ? 'yes' : 'no' ) );
			$this->client->out( '       - Protected: '.$item->protected );
		}
	}

	protected function showModuleFiles( $module ){
		if( !count( $module->files ) )
			return;
		$this->client->out( ' - Included files: ' );
		foreach( $module->files as $sectionKey => $sectionFiles ){
			if( !count( $sectionFiles ) )
				continue;
			$this->client->out( '    - '.ucfirst( $sectionKey ) );
			foreach( $sectionFiles as $file ){
				$line	= $file->file;
				$attr	= array();
				if( $file->type === 'style' ){
					if( !empty( $file->source ) )
						$attr['source']	= $file->source;
					if( !empty( $file->load ) )
						$attr['load']	= $file->load;
				}
				if( $file->type === 'image' ){
					if( !empty( $file->source ) )
						$attr['source']	= $file->source;
				}
				if( count( $attr ) ){
					foreach( $attr as $key => $value ){
						$attr[$key]	= '@'.$key.': '.$value;
					}
					$line .= ' ('.join( ', ', $attr ).')';
				}
				$this->client->out( '       - '.$line );
			}
		}
	}

	protected function showModuleHook( $module ){
		if( !count( $module->hooks ) )
			return;
		$this->client->out( ' - Hooks: ' );
		foreach( $module->hooks as $resource => $events ){
			foreach( $events as $event => $functions ){
				foreach( $functions as $function ){
					if( !preg_match( '/\n/', $function ) )
						$this->client->out( '    - '.$resource.' > '.$event.' >> '.$function );
					else
						$this->client->out( '    - '.$resource.' > '.$event.' >> <func> !DEPRECATED!' );
				}
			}
		}
	}

	protected function showModuleRelations( $module ){
		if( count( $module->relations->needs ) ){
			$this->client->out( ' - Modules needed: ' );
			foreach( $module->relations->needs as $moduleId => $relation )
				$this->client->out( '    - '.$moduleId );
		}
		if( count( $module->relations->supports ) ){
			$this->client->out( ' - Modules supported: ' );
			foreach( $module->relations->supports as $moduleId => $relation )
				$this->client->out( '    - '.$moduleId );
		}
	}

	protected function showModuleVersions( $module ){
		if( !count( $module->versionLog ) )
			return;
		$this->client->out( ' - Versions: ' );
		foreach( $module->versionLog as $item )
			$this->client->out( '    - '.str_pad( $item->version, 10, ' ', STR_PAD_RIGHT ).' '.$item->note );
	}
}
