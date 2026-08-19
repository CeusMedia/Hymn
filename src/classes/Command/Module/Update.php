<?php /** @noinspection PhpUnused */
declare(strict_types=1);

/**
 *	...
 *
 *	Copyright (c) 2014-2026 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Command.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 *	@todo 			handle relations (new relations after update)
 */
class Hymn_Command_Module_Update extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	protected string $installType	= "link";
	protected string $installMode	= "dev";

	public function run(): void
	{
		if( $this->flags->dry )
			$this->out( "## DRY RUN: Simulated actions - no changes will take place." );

		$moduleId	= $this->client->arguments->getArgument();
		if( '' === trim( $moduleId ?? '' ) )
			$this->outError( 'No module ID given.', Hymn_Client::EXIT_ON_INPUT );

		//  apply default install type and mode if not set in application hymn file
		$config		= $this->client->getConfig();											//  shortcut hymn config
		if( isset( $config->application->installType ) )
			$this->installType	= $config->application->installType;
		if( isset( $config->application->installMode ) )
			$this->installMode	= $config->application->installMode;

		$library			= $this->getLibrary();													//  get availableModule library instance
		$listInstalled		= $library->listInstalledModules();										//  get list of installed modules

		//  check if installed
		if( !array_key_exists( $moduleId, $listInstalled ) )										//  availableModule is not installed, no update
			$this->outError( sprintf(
				"Module '%s' is not installed and cannot be updated",
				$moduleId
			), Hymn_Client::EXIT_ON_RUN );
		$installedModule	= $listInstalled[$moduleId];

		//  check if outdated
		$outdatedModules	= $library->getOutdatedModules();										//  prepare empty list of updatable modules
		if( !array_key_exists( $moduleId, $outdatedModules ) && !$this->flags->force )				//  availableModule is not outdated, no update
			$this->outError( sprintf(
				"Module '%s' is not outdated and cannot be updated",
				$moduleId
			), Hymn_Client::EXIT_ON_RUN );

		$availableModule	= $library->getAvailableModule( $installedModule->id, $installedModule->install->source );

		$updater	= new Hymn_Module_Updater( $this->client, $library );

		try{
			$this->client->getFramework()->checkModuleSupport( $availableModule );
			$installType	= $this->client->getModuleInstallType( $availableModule->id, $this->installType );
			$message		= vsprintf( 'Updating availableModule "%s" from %s to %s as %s ...', [
				$availableModule->id,
				$installedModule->version->current,
				$availableModule->version->current,
				$installType
			] );
			$this->out( $message );
			$updater->update( $availableModule, $installType );
		}
		catch( Exception $e ){
			$this->outError( 'Error: '.$e->getMessage().'.', Hymn_Client::EXIT_ON_EXEC );					//  error, but continue, not exit
		}

	}
}