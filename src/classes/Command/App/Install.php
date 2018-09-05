<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2017 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_App_Install extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected $installType	= "link";

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		if( $this->flags->dry )
			$this->client->out( "## DRY RUN: Simulated actions - no changes will take place." );

		$config		= $this->client->getConfig();
		$this->client->setupDatabaseConnection();													//  setup connection to database
		$library	= $this->getLibrary();
		$relation	= new Hymn_Module_Graph( $this->client, $library );

		$moduleId	= trim( $this->client->arguments->getArgument() );
		if( $moduleId ){
			$module			= $library->getModule( $moduleId );
			if( $module ){
				$installType	= $this->client->getModuleInstallType( $moduleId, $this->installType );
				$relation->addModule( $module, $installType );
			}
		}
		else{
			foreach( $config->modules as $moduleId => $module ){
				if( preg_match( "/^@/", $moduleId ) )
					continue;
				if( !isset( $module->active ) || $module->active ){
					$module			= $library->getModule( $moduleId );
					$installType	= $this->client->getModuleInstallType( $moduleId, $this->installType );
					$relation->addModule( $module, $installType );
				}
			}
		}

		$installer	= new Hymn_Module_Installer( $this->client, $library );
		$modules	= $relation->getOrder();
		foreach( $modules as $module ){
			$installType	= $this->client->getModuleInstallType( $module->id );
//			$installMode	= $this->client->getModuleInstallMode( $module->id );
			$listInstalled	= $library->listInstalledModules();
			$isInstalled	= array_key_exists( $module->id, $listInstalled );
			$isCalledModule	= $moduleId && $moduleId == $module->id;
			$isForced		= $this->flags->force && ( $isCalledModule || !$moduleId );
			if( $isInstalled && !$isForced ){
				if( !$this->flags->quiet )
					$this->client->out( "Module '".$module->id."' is already installed" );
			}
			else{
				if( !$this->flags->quiet ){
					$this->client->out( sprintf(
						"%sInstalling module '%s' version %s as %s ...",
						$this->flags->dry ? 'Dry: ' : '',
						$module->id,
						$module->version,
						$installType
					) );
				}
				$installType	= $this->client->getModuleInstallType( $module->id, $installType );
				if( !$this->flags->dry )
					$installer->install( $module, $installType );
			}
		}

/*		//  todo: custom install mode: define SQL to import in hymn file
		if( isset( $config->database->import ) ){
			foreach( $config->database->import as $import ){
				if( file_exists( $import ) )
					$installer->executeSql( file_get_contents( $import ) );							//  broken on this point since extraction to Hymn_Module_SQL
			}
		}*/
	}
}
