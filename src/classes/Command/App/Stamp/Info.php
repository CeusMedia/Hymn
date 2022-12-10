<?php
/**
 *	...
 *
 *	Copyright (c) 2017-2022 Christian Würker (ceusmedia.de)
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
 *	@copyright		2017-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App.Base.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
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
	public function run()
	{
		$pathName	= $this->client->arguments->getArgument( 0 );
		$types		= $this->client->arguments->getArgument( 1 );
		$shelfId	= $this->client->arguments->getArgument( 2 );
		$moduleId	= $this->client->arguments->getArgument( 3 );
//		$shelfId	= $this->evaluateShelfId( $shelfId );
//		$modules	= $this->getInstalledModules( $shelfId );									//  load installed modules
		$stamp		= $this->getStamp( $pathName, $shelfId );


		$types		= !$types || $types === '*' ? 'all' : $types;
		$types		= preg_split( '/\s*,\s*/', $types );

		$allowedTypes	= ['all', 'config', 'files', 'hooks', 'relations'];

		foreach( $types as $type ){
			if( !in_array( $type, $allowedTypes ) )
				$this->client->outError( 'Invalid type: '.$type, Hymn_Client::EXIT_ON_RUN );
			if( $type === 'all' ){
				$types	= $allowedTypes;
				break;
			}
		}

		foreach( $stamp->modules as $module ){
			if( $moduleId && $moduleId !== $module->id )
				continue;
			if( $shelfId && $shelfId !== 'all' && $shelfId !== $module->installSource )
				continue;
			$this->client->out( 'Module: '.$module->title );
			$this->client->out( str_repeat( '-', 48 ) );

			$frameworks = [];
			foreach( $module->frameworks as $frameworkIdentifier => $frameworkVersion )
				$frameworks[]	= $frameworkIdentifier.'@'.$frameworkVersion;
			$frameworks = join( ' | ', $frameworks );

			$this->client->out( $module->title );
			if( $module->description )
				$this->client->out( $module->description );
			$this->client->out( ' - Category:     '.$module->category );
			$this->client->out( ' - Source:       '.$module->installSource );
			$this->client->out( ' - Version:      '.$module->version );
			$this->client->out( ' - Frameworks:   '.$frameworks );

			$moduleInfo	= new Hymn_Module_Info( $this->client );
			if( in_array( 'files', $types ) )
				$moduleInfo->showModuleFiles( $module );
			if( in_array( 'config', $types ) )
				$moduleInfo->showModuleConfig( $module );
			if( in_array( 'relations', $types ) )
				$moduleInfo->showModuleRelations( $this->getLibrary(), $module );
			if( in_array( 'hooks', $types ) )
				$moduleInfo->showModuleHook( $module );
			$this->client->out( '' );

//			$this->client->out( json_encode( $module, JSON_PRETTY_PRINT ) );
		}
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
	 *	@param		$pathName		...
	 *	@param		$shelfId		...
	 *	@return		array
	 */
	protected function getStamp( string $pathName, string $shelfId )
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
		$data	= json_decode( trim( file_get_contents( $fileName ) ) );
		foreach( $data->modules as $module ){
			$module->hooks	= (array) $module->hooks;
		}
		return $data;
	}
}
