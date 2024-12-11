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
 *	@package		CeusMedia.Hymn.Command.App.Base.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App.Base.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_App_Stamp_Info extends Hymn_Command_Abstract implements Hymn_Command_Interface
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
		$types		= $this->client->arguments->getArgument( 1 );
		$sourceId	= $this->client->arguments->getArgument( 2 );
		$moduleId	= $this->client->arguments->getArgument( 3 );
//		$sourceId	= $this->evaluateSourceId( $sourceId );
//		$modules	= $this->getInstalledModules( $sourceId );									//  load installed modules

		$stamp		= $this->getStamp( $pathName, $sourceId );

		$types		= ( !$types || '*' === $types ) ? 'all' : $types;
		/** @var array $typeList */
		$typeList	= preg_split( '/\s*,\s*/', $types );

		$allowedTypes	= ['all', 'config', 'files', 'hooks', 'relations'];

		foreach( $typeList as $type ){
			if( !in_array( $type, $allowedTypes ) )
				$this->client->outError( 'Invalid type: '.$type, Hymn_Client::EXIT_ON_RUN );
			if( 'all' === $type ){
				$typeList	= $allowedTypes;
				break;
			}
		}

		foreach( $stamp->modules as $module ){
			if( $moduleId && $moduleId !== $module->id )
				continue;
			if( $sourceId && $sourceId !== 'all' && $sourceId !== $module->install->source )
				continue;
			$this->out( 'Module: '.$module->title );
			$this->out( str_repeat( '-', 48 ) );

			$frameworks = [];
			foreach( $module->frameworks as $frameworkIdentifier => $frameworkVersion )
				$frameworks[]	= $frameworkIdentifier.'@'.$frameworkVersion;
			$frameworks = join( ' | ', $frameworks );

			$this->out( $module->title );
			if( $module->description )
				$this->out( $module->description );
			$this->out( ' - Category:     '.$module->category );
			$this->out( ' - Source:       '.$module->install->source );
			$this->out( ' - Version:      '.$module->version->current );
			$this->out( ' - Frameworks:   '.$frameworks );

			$moduleInfo	= new Hymn_Module_Info( $this->client );
			if( in_array( 'files', $typeList ) )
				$moduleInfo->showModuleFiles( $module );
			if( in_array( 'config', $typeList ) )
				$moduleInfo->showModuleConfig( $module );
			if( in_array( 'relations', $typeList ) )
				$moduleInfo->showModuleRelations( $this->getLibrary(), $module );
			if( in_array( 'hooks', $typeList ) )
				$moduleInfo->showModuleHook( $module );
			$this->out( '' );

//			$this->out( json_encode( $module, JSON_PRETTY_PRINT ) );
		}
	}

	protected function getLatestStamp( ?string $path = NULL, ?string $sourceId = NULL ): ?string
	{
		$pathDump	= $this->client->getConfigPath().'dumps/';
		$path		= preg_replace( '@\.+/@', '', $path );
		$path		= rtrim( $path, '/' );
		$path		= trim( $path ) ? $path.'/' : $pathDump;
		$this->client->outVerbose( "Scanning folder ".$path." ..." );
		$pattern	= '/^stamp_[0-9:_-]+\.serial$/';
		if( $sourceId )
			$pattern	= '/^stamp_'.preg_quote( $sourceId, '/' ).'_[0-9:_-]+\.serial$/';

		$finder		= new Hymn_Tool_LatestFile( $this->client );
		$finder->setFileNamePattern( $pattern );
		$finder->setAcceptedFileNames( ['latest.serial'] );
		return $finder->find( $path );
	}

	/**
	 *	...
	 *	@access		protected
	 *	@param		$pathName		...
	 *	@param		$sourceId		...
	 *	@return		Hymn_Structure_Stamp
	 */
	protected function getStamp( string $pathName, string $sourceId ): Hymn_Structure_Stamp
	{
		if( $pathName ){
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
	}
}
