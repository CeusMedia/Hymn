<?php
/**
 *	...
 *
 *	Copyright (c) 2017-2021 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Command.App.Stamp
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App.Stamp
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_App_Stamp_Diff extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run()
	{
		$pathName	= $this->client->arguments->getArgument( 0 );
		$type		= $this->client->arguments->getArgument( 1 );
		$shelfId	= $this->client->arguments->getArgument( 2 );
		$moduleId	= $this->client->arguments->getArgument( 3 );
		$shelfId	= $this->evaluateShelfId( $shelfId );
		$modules	= $this->getInstalledModules( $shelfId );									//  load installed modules
		$stamp		= $this->getStamp( $pathName, $shelfId );
		if( $moduleId )
			$modules	= array( $moduleId => $this->getLibrary()->readInstalledModule( $moduleId, $shelfId ) );

		/*  --  FIND MODULE CHANGES  --  */
		$moduleChanges	= $this->detectModuleChanges( $stamp, $modules );
		if( !$moduleChanges ){
			if( !$this->flags->quiet )
				$this->client->out( 'No modules have changed.' );
			return;
		}
		if( !$this->flags->quiet )
			$this->client->out( 'Found '.count( $moduleChanges ).' modules have changed:' );

		foreach( $moduleChanges as $moduleChange )
			if( $moduleChange->type === 'added' )
				$this->showAddedModule( $type, $moduleChange->module );

		foreach( $moduleChanges as $moduleChange )
			if( $moduleChange->type === 'changed' )
				$this->showChangedModule( $type, $moduleChange->source, $moduleChange->target );

		foreach( $moduleChanges as $moduleChange )
			if( $moduleChange->type === 'removed' )
				$this->showRemovedModule( $type, $moduleChange->module );
	}

	protected function detectModuleChanges( $stamp, array $modules ): array
	{
		$moduleChanges	= array();
		foreach( $modules as $module ){
			if( !isset( $stamp->modules->{$module->id} ) ){
				$moduleChanges[$module->id]	= (object) array(
					'type'		=> 'added',
					'module'	=> $module,
				);
			}
			else{
				$oldModule	= $stamp->modules->{$module->id};
				if( !version_compare( $oldModule->version, $module->version, '<' ) )
					continue;
				$moduleChanges[$module->id]	= (object) array(
					'type'		=> 'changed',
					'source'	=> $oldModule,
					'target'	=> $module,
				);
			}
		}
		return $moduleChanges;
	}

	protected function getInstalledModules( ?string $shelfId = NULL )
	{
		$modules	= $this->getLibrary()->listInstalledModules( $shelfId );
		$message	= 'Found '.count( $modules ).' installed modules.';
		if( $shelfId )
			$message	= 'Found '.count( $modules ).' installed modules in source '.$shelfId.'.';
		$this->client->outVerbose( $message );
		return $modules;
	}

	protected function getLatestStamp( ?string $path = NULL, ?string $shelfId = NULL )
	{
		$pathDump	= $this->client->getConfigPath().'dumps/';
		$path		= preg_replace( '@\.+/@', '', $path );
		$path		= rtrim( $path, '/' );
		$path		= trim( $path ) ? $path.'/' : $pathDump;
		$this->client->outVerbose( "Scanning folder ".$path." ..." );
		$pattern	= '/^stamp_[0-9:_-]+\.json$/';
		if( $shelfId )
			$pattern	= '/^stamp_'.preg_quote( $shelfId, '/' ).'_[0-9:_-]+\.json$/';

		$finder		= new Hymn_Tool_LatestFile( $this->client );
		$finder->setFileNamePattern( $pattern );
		$finder->setAcceptedFileNames( array( 'latest.json' ) );
		return $finder->find( $path );
	}

	/**
	 *	...
	 *	@access		protected
	 *	@param		$pathName		...
	 *	@param		$shelfId		...
	 *	@return		array
	 */
	protected function getStamp( $pathName, string $shelfId )
	{
		if( $pathName ){
			$fileName	= NULL;
			if( $pathName === 'latest' )
				$fileName	= $this->getLatestStamp( NULL, $shelfId );
			else if( file_exists( $pathName ) && is_dir( $pathName ) )
				$fileName	= $this->getLatestStamp( $pathName, $shelfId );
			else if( file_exists( $pathName ) )
				$fileName	= $pathName;
		}
		else
			$fileName		= $this->getLatestStamp( NULL, $shelfId );
		if( !( $fileName && file_exists( $fileName ) ) )
			$this->client->outError( 'No comparable stamp file found.', Hymn_Client::EXIT_ON_RUN );
		$this->client->outVerbose( 'Loading stamp: '.$fileName );
		return json_decode( trim( file_get_contents( $fileName ) ) );
	}

	/**
	 *	Calculates difference of added module and print out results.
	 *	@access		protected
	 *	@param		string		$type		Diff type, one of [all, sql, config(, files)]
	 *	@param		object		$module		Module that has been added (maybe from library)
	 *	@return		void
	 */
	protected function showAddedModule( string $type, $module )
	{
		$sql	= new Hymn_Module_SQL( $this->client );
		if( !$this->flags->quiet )
			$this->client->out( ' - Module added: '.$module->id );
		if( in_array( $type, array( NULL, 'all', 'sql' ) ) ){
			$scripts	= $sql->getModuleInstallSql( $module );
			if( $scripts ){
				if( !$this->flags->quiet )
					$this->client->out( '   SQL: '.count( $scripts ).' installation(s):' );
				$this->client->outVerbose( '--  INSTALL '.strtoupper( $module->id ).'  --' );
				foreach( array_values( $scripts ) as $nr => $script ){
					$this->client->outVerbose( vsprintf( '--  UPDATE (%d/%d) version %s', array(
						$nr + 1,
						count( $scripts ),
						$script->version
					) ) );
					$this->client->out( trim( $script->sql ) );
					$version	= $script->version;
				}
			}
		}
		if( in_array( $type, array( NULL, 'all', 'config' ) ) ){
		}
	}

	/**
	 *	Calculates difference of module change and print out results.
	 *	@access		protected
	 *	@param		string		$type		Diff type, one of [all, sql, config(, files)]
	 *	@param		object		$moduleOld	Old version of module (maybe from stamp)
	 *	@param		object		$moduleNew	New version of module (maybe from library)
	 *	@return		void
	 */
	protected function showChangedModule( string $type, $moduleOld, $moduleNew )
	{
		$diff	= new Hymn_Module_Diff( $this->client, $this->library );
		if( !$this->flags->quiet )
			$this->client->out( ' - Module changed: '.$moduleNew->id );
		if( in_array( $type, array( NULL, 'all', 'sql' ) ) ){
			if( ( $scripts = $diff->compareSqlByModules( $moduleOld, $moduleNew ) ) ){
				if( !$this->flags->quiet )
					$this->client->out( '   SQL: '.count( $scripts ).' update(s):' );
				$this->client->outVerbose( '--  UPDATE '.strtoupper( $moduleNew->id ).'  --' );
				$version	= $moduleOld->version;
				foreach( array_values( $scripts ) as $nr => $script ){
					$this->client->outVerbose( vsprintf( '--  UPDATE (%d/%d) version %s-> %s', array(
						$nr + 1,
						count( $scripts ),
						$version,
						$script->version
					) ) );
					$this->client->out( trim( $script->query ) );
					$version	= $script->version;
				}
			}
		}
		if( in_array( $type, array( NULL, 'all', 'config' ) ) ){
			$changes	= $keys = $diff->compareConfigByModules( $moduleOld, $moduleNew );
			foreach( $changes as $change ){
				if( $change->status === 'removed' ){
					$message	= '   - %s has been removed.';
					$this->client->out( vsprintf( $message, array( $change->key ) ) );
				}
				else if( $change->status === 'added' ){
					$message	= '   - %s has been added with default value: %s';
					$this->client->out( vsprintf( $message, array(
						$change->key,
						$change->value
					) ) );
				}
				else if( $change->status === 'changed' ){
					foreach( $change->properties as $property ){
						$message	= '   - %s: %s has changed from %s to %s';
						$this->client->out( vsprintf( $message, array(
							$change->key,
							$property->key,
							$property->valueOld,
							$property->valueNew
						) ) );
					}
				}
			}
		}
	}

	/**
	 *	Calculates difference of removed module and print out results.
	 *	@access		protected
	 *	@param		string		$type		Diff type, one of [all, sql, config(, files)]
	 *	@param		object		$module		Module that has been removed (maybe from stamp)
	 *	@return		void
	 */
	protected function showRemovedModule( $type, $module )
	{
		$this->client->outError( 'showRemovedModule: Not implemented, yet.' );
	}
}
