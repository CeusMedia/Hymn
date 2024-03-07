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
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Stamp
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
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
	public function run()
	{
		$pathName	= $this->client->arguments->getArgument();
		$type		= $this->client->arguments->getArgument( 1 );
		$shelfId	= $this->client->arguments->getArgument( 2 );
		$moduleId	= $this->client->arguments->getArgument( 3 );
		$shelfId	= $this->evaluateShelfId( $shelfId );
		$modules	= $this->getAvailableModules( $shelfId );									//  load available modules


		/** @var object{modules: array} $stamp */
		$stamp		= $this->getStamp( $pathName, $shelfId );

		$stampModules	= (array) $stamp->modules;
		$this->client->outVerbose( 'Found '.count( $stampModules ).' modules in stamp.' );
		if( $moduleId ){
			if( !isset( $stamp->modules->{$moduleId} ) )
				$this->client->outError( 'Module "'.$moduleId.'" is not in stamp.', Hymn_Client::EXIT_ON_RUN );
			$stamp->modules	= (object) [$moduleId => $stamp->modules->{$moduleId}];
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

		foreach( $moduleChanges as $moduleChange )
			if( $moduleChange->type === 'changed' )
				$this->showChangedModule( $type, $moduleChange->source, $moduleChange->target );
	}

	protected function detectModuleChanges( $stamp, $modules ): array
	{
		$moduleChanges	= [];
		foreach( $modules as $module ){
			if( !isset( $stamp->modules->{$module->id} ) )
				continue;
			$oldModule	= $stamp->modules->{$module->id};
			if( !version_compare( $oldModule->version, $module->version, '<' ) )
				continue;
			$moduleChanges[$module->id]	= (object) [
				'type'		=> 'changed',
				'source'	=> $oldModule,
				'target'	=> $module,
			];
		}
		return $moduleChanges;
	}

	protected function getAvailableModules( ?string $shelfId = NULL ): array
	{
		$modules	= $this->getLibrary()->getAvailableModules( $shelfId );
		$message	= 'Found '.count( $modules ).' available modules.';
		if( $shelfId )
			$message	= 'Found '.count( $modules ).' available modules in source '.$shelfId.'.';
		$this->client->outVerbose( $message );
		return $modules;
	}

	protected function getLatestStamp( ?string $path = NULL, ?string $shelfId = NULL ): ?string
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
		$finder->setAcceptedFileNames( ['latest.json'] );
		return $finder->find( $path );
	}

	/**
	 *	...
	 *	@access		protected
	 *	@param		string		$pathName		...
	 *	@param		string		$shelfId		...
	 *	@return		object
	 */
	protected function getStamp( string $pathName, string $shelfId ): object
	{
		if( '' !== $pathName ){
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
		if( !( NULL !== $fileName && file_exists( $fileName ) ) )
			$this->client->outError( 'No comparable stamp file found.', Hymn_Client::EXIT_ON_RUN );
		$this->client->outVerbose( 'Loading stamp: '.$fileName );
		return json_decode( trim( file_get_contents( $fileName ) ) );
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
					$this->out( trim( $script->query ) );
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
}
