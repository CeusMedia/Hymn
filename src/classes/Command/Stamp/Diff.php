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
 *	@package		CeusMedia.Hymn.Command.Stamp
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Stamp
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_Stamp_Diff extends Hymn_Command_Abstract implements Hymn_Command_Interface
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
		$pathName	= $this->client->arguments->getArgument();
		$type		= $this->client->arguments->getArgument( 1 );
		$sourceId	= $this->client->arguments->getArgument( 2 );
		$moduleId	= $this->client->arguments->getArgument( 3 );
		$sourceId	= $this->evaluateSourceId( $sourceId );
		$modules	= $this->getAvailableModules( $sourceId );									//  load available modules

		$stamp		= $this->getStamp( $pathName, $sourceId );

		$stampModules	= (array) $stamp->modules;
		$this->client->outVerbose( 'Found '.count( $stampModules ).' modules in stamp.' );
		if( $moduleId ){
			if( !isset( $stamp->modules[$moduleId] ) )
				$this->client->outError( 'Module "'.$moduleId.'" is not in stamp.', Hymn_Client::EXIT_ON_RUN );
		}

		/*  --  FIND MODULE CHANGES  --  */
		$moduleChanges	= $this->detectModuleChanges( $stamp, $modules );
		if( !$moduleChanges ){
			if( !$this->flags->quiet )
				$this->out( 'No modules have changed.' );
			return;
		}
		if( !$this->flags->quiet )
			$this->out( 'Found '.count( $moduleChanges ).' modules have changed:' );

		/** @var object{type: string, source: Hymn_Structure_Module, target: Hymn_Structure_Module} $moduleChange */
		foreach( $moduleChanges as $moduleChange )
			if( $moduleChange->type === 'changed' )
				$this->showChangedModule( $type, $moduleChange->source, $moduleChange->target );
	}

	/**
	 *	@param		object{modules: array<Hymn_Structure_Module>}	$stamp
	 *	@param		array<Hymn_Structure_Module>					$modules
	 *	@return		array<string,object{type: string, source: Hymn_Structure_Module, target: Hymn_Structure_Module}>
	 */
	protected function detectModuleChanges( object $stamp, array $modules ): array
	{
		$moduleChanges	= [];
		foreach( $modules as $module ){
			if( !isset( $stamp->modules[$module->id] ) )
				continue;
			$oldModule	= $stamp->modules[$module->id];
			if( !version_compare( $oldModule->version->current, $module->version->current, '<' ) )
				continue;
			$moduleChanges[$module->id]	= (object) [
				'type'		=> 'changed',
				'source'	=> $oldModule,
				'target'	=> $module,
			];
		}
		return $moduleChanges;
	}

	protected function getAvailableModules( ?string $sourceId = NULL ): array
	{
		$modules	= $this->getLibrary()->getAvailableModules( $sourceId );
		$message	= 'Found '.count( $modules ).' available modules.';
		if( $sourceId )
			$message	= 'Found '.count( $modules ).' available modules in source '.$sourceId.'.';
		$this->client->outVerbose( $message );
		return $modules;
	}

	protected function getLatestStamp( ?string $path = NULL, ?string $sourceId = NULL ): ?string
	{
		$pathDump	= $this->client->getConfigPath().'dumps/';
		$path		= preg_replace( '@\.+/@', '', $path );
		$path		= rtrim( $path, '/' );
		$path		= trim( $path ) ? $path.'/' : $pathDump;
		$this->client->outVerbose( "Scanning folder ".$path." ..." );
		$pattern	= '/^stamp_[0-9:_-]+\.json$/';
		if( $sourceId )
			$pattern	= '/^stamp_'.preg_quote( $sourceId, '/' ).'_[0-9:_-]+\.json$/';

		$finder		= new Hymn_Tool_LatestFile( $this->client );
		$finder->setFileNamePattern( $pattern );
		$finder->setAcceptedFileNames( ['latest.json'] );
		return $finder->find( $path );
	}

	/**
	 *	...
	 *	@access		protected
	 *	@param		string		$pathName		...
	 *	@param		string		$sourceId		...
	 *	@return		Hymn_Structure_Stamp
	 */
	protected function getStamp( string $pathName, string $sourceId ): Hymn_Structure_Stamp
	{
		if( '' !== $pathName ){
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
		if( !( NULL !== $fileName && file_exists( $fileName ) ) )
			$this->client->outError( 'No comparable stamp file found.', Hymn_Client::EXIT_ON_RUN );
		$this->client->outVerbose( 'Loading stamp: '.$fileName );
		return unserialize( trim( file_get_contents( $fileName ) ) );
	}

	/**
	 *	Calculates difference of module change and print out results.
	 *	@access		protected
	 *	@param		string		$type		Diff type, one of [all, sql, config(, files)]
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
			$changes	= $diff->compareConfigByModules( $moduleOld, $moduleNew );
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
}
