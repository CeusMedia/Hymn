<?php
/**
 *	...
 *
 *	Copyright (c) 2017-2024 Christian Würker (ceusmedia.de)
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
 *	@copyright		2017-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App.Stamp
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
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
	public function run(): void
	{
		$pathName	= $this->client->arguments->getArgument() ?? '';
		$type		= $this->client->arguments->getArgument( 1 ) ?? '';
		$moduleId	= $this->client->arguments->getArgument( 3 ) ?? '';
		$sourceId	= $this->evaluateSourceId( $this->client->arguments->getArgument( 2 ) );
		$modules	= $this->getInstalledModules( $sourceId );									//  load installed modules
		$stamp		= $this->getStamp( $pathName, $sourceId );
		if( '' !== $moduleId )
			$modules	= [$moduleId => $this->getLibrary()->readInstalledModule( $moduleId )];

		/*  --  FIND MODULE CHANGES  --  */
		$moduleChanges	= $this->detectModuleChanges( $stamp, $modules );
		if( !$moduleChanges ){
			if( !$this->flags->quiet )
				$this->out( 'No modules have changed.' );
			return;
		}
		if( !$this->flags->quiet )
			$this->out( 'Found '.count( $moduleChanges ).' modules have changed:' );

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

	/**
	 *	@param		object{modules: array<Hymn_Structure_Module>}		$stamp
	 *	@param		array<string,Hymn_Structure_Module>	$modules
	 *	@return		array<string,object{type: string, module: ?Hymn_Structure_Module, source: ?Hymn_Structure_Module, target: ?Hymn_Structure_Module}>
	 */
	protected function detectModuleChanges( object $stamp, array $modules ): array
	{
		$moduleChanges	= [];
		foreach( $modules as $module ){
			if( !isset( $stamp->modules[$module->id] ) ){
				$moduleChanges[$module->id]	= (object) [
					'type'		=> 'added',
					'module'	=> $module,
					'source'	=> NULL,
					'target'	=> NULL,
				];
			}
			else{
				$oldModule	= $stamp->modules[$module->id];
				if( !version_compare( $oldModule->version->current, $module->version->current, '<' ) )
					continue;
				$moduleChanges[$module->id]	= (object) [
					'type'		=> 'changed',
					'source'	=> $oldModule,
					'target'	=> $module,
					'module'	=> NULL,
				];
			}
		}
		return $moduleChanges;
	}

	/**
	 *	@param		string|NULL		$sourceId
	 *	@return		array<string,Hymn_Structure_Module>
	 */
	protected function getInstalledModules( ?string $sourceId = NULL ): array
	{
		$modules	= $this->getLibrary()->listInstalledModules( $sourceId );
		$message	= 'Found '.count( $modules ).' installed modules.';
		if( $sourceId )
			$message	= 'Found '.count( $modules ).' installed modules in source '.$sourceId.'.';
		$this->client->outVerbose( $message );
		return $modules;
	}

	protected function getLatestStamp( ?string $path = NULL, ?string $sourceId = NULL ): ?string
	{
		$pathDump	= $this->client->getConfigPath().'dumps/';
		$path		= preg_replace( '@\.+/@', '', $path ?? '' );
		$path		= rtrim( $path, '/' );
		$path		= trim( $path ) ? $path.'/' : $pathDump;
		$this->client->outVerbose( "Scanning folder ".$path." ..." );
		$pattern	= '/^stamp_[0-9:_-]+\.serial/';
		if( $sourceId )
			$pattern	= '/^stamp_'.preg_quote( $sourceId, '/' ).'_[0-9:_-]+\.serial/';

		$finder		= new Hymn_Tool_LatestFile( $this->client );
		$finder->setFileNamePattern( $pattern );
		$finder->setAcceptedFileNames( ['latest.serial'] );
		return $finder->find( $path );
	}

	/**
	 *	...
	 *	@access		protected
	 *	@param		string			$pathName		...
	 *	@param		string|NULL		$sourceId		...
	 *	@return		object{modules: array<Hymn_Structure_Module>}
	 */
	protected function getStamp( string $pathName, ?string $sourceId = NULL ): object
	{
		if( '' !== trim( $pathName ) ){
			$fileName	= NULL;
			if( $pathName === 'latest' )
				$fileName	= $this->getLatestStamp( NULL, $sourceId );
			else if( file_exists( $pathName ) && is_dir( $pathName ) )
				$fileName	= $this->getLatestStamp( $pathName, $sourceId );
			else if( file_exists( $pathName ) )
				$fileName	= $pathName;
		}
		else
			$fileName		= $this->getLatestStamp( NULL, $sourceId );
		if( !( $fileName && file_exists( $fileName ) ) )
			$this->client->outError( 'No comparable stamp file found.', Hymn_Client::EXIT_ON_RUN );
		$this->client->outVerbose( 'Loading stamp: '.$fileName );
		return unserialize( file_get_contents( $fileName ) );
//		return Hymn_Tool_ConfigFile::read( $fileName );
	}

	/**
	 *	Calculates difference of added module and print out results.
	 *	@access		protected
	 *	@param		string		$type		Diff type, one of [all, sql, config(, files)]
	 *	@param		Hymn_Structure_Module	$module		Module that has been added (maybe from library)
	 *	@return		void
	 */
	protected function showAddedModule( string $type, Hymn_Structure_Module $module ): void
	{
		$sql	= new Hymn_Module_SQL( $this->client );
		if( !$this->flags->quiet )
			$this->out( ' - Module added: '.$module->id );
		if( in_array( $type, [NULL, 'all', 'sql'] ) ){
			$scripts	= $sql->getModuleInstallSql( $module );
			if( $scripts ){
				if( !$this->flags->quiet )
					$this->out( '   SQL: '.count( $scripts ).' installation(s):' );
				$this->client->outVerbose( '--  INSTALL '.strtoupper( $module->id ).'  --' );
				foreach( array_values( $scripts ) as $nr => $script ){
					$this->client->outVerbose( vsprintf( '--  UPDATE (%d/%d) version %s', array(
						$nr + 1,
						count( $scripts ),
						$script->version
					) ) );
					$this->out( trim( $script->sql ) );
					$version	= $script->version;
				}
			}
		}
		if( in_array( $type, [NULL, 'all', 'config'] ) ){
		}
	}

	/**
	 *	Calculates difference of module change and print out results.
	 *	@access		protected
	 *	@param		string					$type		Diff type, one of [all, sql, config(, files)]
	 *	@param		Hymn_Structure_Module	$moduleOld	Old version of module (maybe from stamp)
	 *	@param		Hymn_Structure_Module	$moduleNew	New version of module (maybe from library)
	 *	@return		void
	 */
	protected function showChangedModule( string $type, Hymn_Structure_Module $moduleOld, Hymn_Structure_Module $moduleNew ): void
	{
		$diff	= new Hymn_Module_Diff( $this->client, $this->library );
		if( !$this->flags->quiet )
			$this->out( ' - Module changed: '.$moduleNew->id );
		if( in_array( $type, [NULL, 'all', 'sql'] ) ){
			if( ( $scripts = $diff->compareSqlByModules( $moduleOld, $moduleNew ) ) ){
				if( !$this->flags->quiet )
					$this->out( '   SQL: '.count( $scripts ).' update(s):' );
				$this->client->outVerbose( '--  UPDATE '.strtoupper( $moduleNew->id ).'  --' );
				$version	= $moduleOld->version;
				foreach( array_values( $scripts ) as $nr => $script ){
					$this->client->outVerbose( vsprintf( '--  UPDATE (%d/%d) version %s-> %s', array(
						$nr + 1,
						count( $scripts ),
						$version,
						$script->version
					) ) );
					$this->out( trim( $script->sql ) );
					$version	= $script->version;
				}
			}
		}
		if( in_array( $type, [NULL, 'all', 'config'] ) ){
			$changes	= $keys = $diff->compareConfigByModules( $moduleOld, $moduleNew );
			foreach( $changes as $change ){
				if( $change->status === 'removed' ){
					$message	= '   - %s has been removed.';
					$this->out( vsprintf( $message, [$change->key] ) );
				}
				else if( $change->status === 'added' ){
					$message	= '   - %s has been added with default value: %s';
					$this->out( vsprintf( $message, [
						$change->key,
						$change->value
					] ) );
				}
				else if( $change->status === 'changed' ){
					foreach( $change->properties as $property ){
						$message	= '   - %s: %s has changed from %s to %s';
						$this->out( vsprintf( $message, [
							$change->key,
							$property->key,
							$property->valueOld,
							$property->valueNew
						] ) );
					}
				}
			}
		}
	}

	/**
	 *	Calculates difference of removed module and print out results.
	 *	@access		protected
	 *	@param		string		$type		Diff type, one of [all, sql, config(, files)]
	 *	@param		Hymn_Structure_Module		$module		Module that has been removed (maybe from stamp)
	 *	@return		void
	 */
	protected function showRemovedModule( string $type, Hymn_Structure_Module $module ): void
	{
		$this->client->outError( 'showRemovedModule: Not implemented, yet.' );
	}
}
