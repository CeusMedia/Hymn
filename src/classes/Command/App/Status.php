<?php
/**
 *	Compares installed against available modules to sollect outdated modules.
 *	Shows outdated modules with logged changes, if available and verbose.
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
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	Compares installed against available modules to sollect outdated modules.
 *	Shows outdated modules with logged changes, if available and verbose.
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 *	@todo			implement flags (eg. quiet: return status code)
 */
class Hymn_Command_App_Status extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	const CODE_NONE					= 0;
	const CODE_MODULES_OUTDATED		= 1;

	/**
	 *	Execute this command.
	 *	Implements flags: quiet?
	 *	Missing flags: database-no?(if new 3rd arg would be target database), quiet?
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
	//	$config			= $this->client->getConfig();

		/* @todo	find a better solution
					this is slow, because:
					- list* methods read from disk
 					- list* methods do not use a cache atm (this is prepared but disable)
 					- the module updater will read the list AGAIN
					solutions:
					- have a disk reading "count installed modules" method
					- let module updater cache list on first listing, use cache later
		*/
		$listInstalled	= $this->getLibrary()->listInstalledModules();								//  get list of installed modules
		if( !$listInstalled ){																		//  application has no installed modules
			$this->client->out( 'No installed modules found' );										//  quit with message
			return static::CODE_NONE;
		}

		$moduleUpdater		= new Hymn_Module_Updater( $this->client, $this->getLibrary() );		//  use module updater on current application installation
		$outdatedModules	= $moduleUpdater->getUpdatableModules();								//  get list of outdated modules

		$moduleId	= trim( $this->client->arguments->getArgument( 0 ) );							//  get module id as first argument
		if( strlen( $moduleId = trim( $moduleId ) ) )												//  a module ID has been given
			return $this->runForSingleModule( $outdatedModules, $moduleId );						//  run status for single module
		return $this->runForAllModules( $outdatedModules );											//  run status for all (outdated) modules
	}

	/*  --  PROTECTED  --  */

	protected function printModuleUpdateChangelog( $update, $indent = '' ){
		$changes	= $this->getLibrary()->getAvailableModuleLogChanges(
			$update->id,
			$update->source,
			$update->installed,
			$update->available
		);
		if( !count( $changes ) )
			return;
		$this->client->out( $indent.'Changes:' );
		foreach( $changes as $change ){
			$version	= str_pad( $change->version, 9, ' ', STR_PAD_RIGHT );
			$this->client->out( sprintf( $indent.' - %s %s', $version, $change->note ) );
		}
	}

	/**
	 *	Produce report for all outdated modules at once.
	 *	Shows version numbers of installed and available modules.
	 *	Shows logged changes in verbose mode.
	 *	All shown information are grouped by modules on alphanumeric order.
	 *	Cancels if no modules are outdated.
	 *
	 *	@protected
	 *	@param		array		$outdatedModules		List of outdated modules to create report about
	 *	@return		int			Return code: 0 - CODE_NONE | 1 - CODE MODULES_OUTDATED
	 */
	protected function runForAllModules( $outdatedModules ){
		$listInstalled	= $this->getLibrary()->listInstalledModules();								//  get list of installed modules
		$message		= '%d installed modules found.';
		$this->client->outVerbose( sprintf( $message, count( $listInstalled ) ) );					//  print status topic: Modules > Installed
		if( !$outdatedModules ){																	//  there are outdated modules
			$this->client->outVerbose( 'No updatable modules found.' );								//  print status topic: Modules > Outdated
			return static::CODE_NONE;
		}
		if( !$this->flags->quiet ){
			$message	= '%d updatable modules found:';
			$this->client->out( sprintf( $message, count( $outdatedModules ) ) );					//  print status topic: Modules > Outdated
		}
		foreach( $outdatedModules as $update ){														//  iterate list of outdated modules
			if( !$this->flags->quiet ){
				$this->client->out( vsprintf( "- %s: %s -> %s", array(								//  print outdated module and:
					$update->id,																	//  - module ID
					$update->installed,																//  - currently installed version
					$update->available																//  - available version
				) ) );
				if( $this->flags->verbose )
					$this->printModuleUpdateChangelog( $update, '  ' );
			}
		}
		return static::CODE_MODULES_OUTDATED;
	}

	/**
	 *	Produce report for one outdated module.
	 *	Shows version numbers of installed and available modules.
	 *	Shows logged changes in verbose mode.
	 *	Cancels if given module ID is not installed or not outdated.
	 *
	 *	@protected
	 *	@param		array		$outdatedModules		List of outdated modules to create report about
	 *	@param		string		$moduleId				ID of outdated module to create report about
	 *	@return		int			Return code: 0 - CODE_NONE | 1 - CODE MODULES_OUTDATED
	 */
	protected function runForSingleModule( $outdatedModules, $moduleId ){
		if( !$this->getLibrary()->isInstalledModule( $moduleId ) ){
			$this->client->out( 'Module '.$moduleId.' is not installed.' );
			return static::CODE_NONE;
		}
		if( !array_key_exists( $moduleId, $outdatedModules ) ){
			$this->client->out( 'Module is up-to-date.' );
			return static::CODE_NONE;
		}
		$update	= $outdatedModules[$moduleId];
		$this->client->out( 'Version installed: '.$update->installed );
		$this->client->out( 'Version available: '.$update->available );
		if( $this->flags->verbose )
			$this->printModuleUpdateChangelog( $update );
		return static::CODE_MODULES_OUTDATED;
	}
}
