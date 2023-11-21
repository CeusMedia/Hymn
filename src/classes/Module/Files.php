<?php
/**
 *	Manager for module files.
 *
 *	Copyright (c) 2014-2022 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	Manager for module files.
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Module_Files
{
	protected Hymn_Client $client;
	protected ?object $config;
	protected object $flags;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		Hymn_Client		$client		Hymn client instance
	 */
	public function __construct( Hymn_Client $client )
	{
		$this->client	= $client;
		$this->config	= $this->client->getConfig();
		$this->flags	= (object) [
			'dry'		=> $this->client->flags & Hymn_Client::FLAG_DRY,
			'force'		=> $this->client->flags & Hymn_Client::FLAG_FORCE,
			'quiet'		=> $this->client->flags & Hymn_Client::FLAG_QUIET,
			'verbose'	=> $this->client->flags & Hymn_Client::FLAG_VERBOSE,
			'noFiles'	=> $this->client->flags & Hymn_Client::FLAG_NO_FILES,
		];
	}

	/**
	 *	Tries to link or copy all module files into application.
	 *	Does nothing if flag 'db' is set to 'only'.
	 *	@access		public
	 *	@param 		object 		$module			Module object
	 *	@param		string		$installType	One of {link, copy}
	 *	@param		boolean		$tryMode		Flag: force no changes, only try (default: no)
	 *	@return		void
	 *	@throws		Exception	if any file manipulation action goes wrong
	 */
	public function copyFiles( object $module, string $installType = 'link', bool $tryMode = FALSE ): bool
	{
		if( $this->flags->noFiles )
			return TRUE;

		$fileMap	= $this->prepareModuleFileMap( $module );
		foreach( $fileMap as $source => $target ){
			self::createPath( dirname( $target ) );
			$pathNameIn	= realpath( $source );
			$pathOut	= dirname( $target );
			if( $installType === "link" ){
//				try{
					if( !$pathNameIn )
						throw new Exception( 'Source file '.$source.' is not existing' );
					if( !is_readable( $pathNameIn ) )
						throw new Exception( 'Source file '.$source.' is not readable' );
//					if( !is_executable( $pathNameIn ) )
//						throw new Exception( 'Source file '.$source.' is not executable' );
					if( !is_dir( $pathOut ) && !self::createPath( $pathOut ) )
						throw new Exception( 'Target path '.$pathOut.' is not creatable' );
					if( !( $this->flags->dry || $tryMode ) ){										//  not a dry or try run
						if( file_exists( $target ) ){
							if( is_file( $target ) && !is_link( $target ) && !$this->flags->force )
								continue;
							@unlink( $target );
						//	if( !$force )
						//		throw new Exception( 'Target file '.$target.' is already existing' );
						}
						if( !@symlink( $source, $target ) )
							throw new Exception( 'Link of source file '.$source.' is not creatable' );
					}
					if( $this->flags->verbose && !$this->flags->quiet )
						$this->client->out( '  … linked file '.$source );
//				}
//				catch( Exception $e ){
//					$this->client->out( 'Link Error: '.$e->getMessage().'.' );
//					return FALSE;
//				}
			}
			else{
//				try{
					if( !$pathNameIn )
						throw new Exception( 'Source file '.$source.' is not existing' );
					if( !is_readable( $pathNameIn ) )
						throw new Exception( 'Source file '.$source.' is not readable' );
					if( !is_dir( $pathOut ) && !self::createPath( $pathOut ) )
						throw new Exception( 'Target path '.$pathOut.' is not creatable' );
					if( !( $this->flags->dry || $tryMode ) ){										//  not a dry or try run
/*						if( file_exists( $target ) ){
							if( is_file( $target ) && !$this->flags->force )
								continue;
							@unlink( $target );
						}
*/						if( !@copy( $source, $target ) )											//  copying failed
							throw new Exception( 'Source file '.$source.' could not been copied' );
					}
					if( $this->flags->verbose && !$this->flags->quiet && !$tryMode )
						$this->client->out( '  … copied file '.$source );
//				}
//				catch( Exception $e ){
//					throw new Exception( 'Copy Error: '.$e->getMessage() );
//				}
			}
		}
		return TRUE;
	}

	/**
	 *	Creates a path.
	 *	A nested path will be created recursively.
	 *	No error messages will be shown but the return value indicates the result.
	 *	Does nothing if flag 'db' is set to 'only'.
	 *	@static
	 *	@access		public
	 *	@param		string		$path		Path to create
	 *	@return		bool|NULL
	 */
	static public function createPath( string $path ): ?bool
	{
		if( file_exists( $path ) )
			return NULL;
		if( @mkdir( $path, 0777, TRUE ) )
			return TRUE;
		return FALSE;
	}

	/**
	 *	Enlist all module files onto a map of source and target files.
	 *	Needs given object to be an available module to map between source and target.
	 *	If not awaiting an available module, an installed module can be given.
	 *	Mapped source and target paths are identical in this case.
	 *	@access		protected
	 *	@param		object		$module		Module object
	 *	@return		array
	 *	@todo   	change behaviour of styles without source: install into common instead of theme
	 */
	protected function prepareModuleFileMap( object $module, bool $awaitAvailableModule = TRUE ): array
	{
		if( !is_object( $module ) )
			throw new InvalidArgumentException( 'Given module object is invalid' );
		if( !isset( $module->path ) ){
			if( $awaitAvailableModule )
				throw new InvalidArgumentException( 'Given module object is an installed module - object of available module needed' );
			$module->path	= $this->config->application->uri;
		}

		$pathSource		= $module->path;
		$pathTarget		= $this->config->application->uri;
		$layoutTheme	= $this->config->layoutTheme ?? 'common';
		$layoutPrimer	= $this->config->layoutPrimer ?? 'primer';
		$map			= [];
		$skipSources	= ['lib', 'styles-lib', 'scripts-lib', 'url'];
		foreach( $module->files as $fileType => $files ){
			foreach( $files as $file ){
				switch( $fileType ){
					case 'files':
						$path	= $file->file;
						$map[$pathSource.$path]	= $pathTarget.$path;
						break;
					case 'classes':
					case 'templates':
						$path	= $fileType.'/'.$file->file;
						$map[$pathSource.$path]	= $pathTarget.$path;
						break;
					case 'locales':
						$path	= $this->config->paths->locales;
						$source	= $pathSource.'locales/'.$file->file;
						$target	= $pathTarget.$path.$file->file;
						$map[$source]	= $target;
						break;
					case 'scripts':
						if( isset( $file->source ) && in_array( $file->source, $skipSources ) )
							continue 2;
						$path	= $this->config->paths->scripts;
						$source	= $pathSource.'js/'.$file->file;
						$target	= $pathTarget.$path.$file->file;
						$map[$source]	= $target;
						break;
					case 'styles':
						if( !isset( $file->source ) )
							$file->source	= 'theme';
						if( in_array( $file->source, $skipSources ) )
							continue 2;
						switch( $file->source ){
							case 'common':
								$theme	= "common";
								break;
							case 'primer':
								$theme	= $layoutPrimer;
								break;
							case 'theme':
							default:
								$theme	= !empty( $file->theme ) ? $file->theme : $layoutTheme;
								break;
						}
						$path	= $pathTarget.$this->config->paths->themes.$theme;
						if( !file_exists( $path ) && !$this->flags->dry )
							self::createPath( $path );
						$source	= $pathSource.'css/'.$file->file;
						$map[$source]	= $path.'/css/'.$file->file;
						break;
					case 'images':
						$source	= $pathSource.'img/'.$file->file;
						if( !isset( $file->source ) )
							$file->source	= 'images';
						if( in_array( $file->source, $skipSources ) )
							continue 2;
						switch( $file->source ){
							case 'common':
								$path	= $this->config->paths->themes.'common/img/';
								break;
							case 'primer':
								$path	= $this->config->paths->themes.$layoutPrimer.'/img/';
								break;
							case 'theme':
								$theme	= !empty( $file->theme ) ? $file->theme : $layoutTheme;
								$path	= $this->config->paths->themes.$theme.'/img/';
								break;
							case 'images':
							default:
								$path	= $this->config->paths->images;
								break;
						}
						if( !file_exists( $pathTarget.$path ) && !$this->flags->dry )
							self::createPath( $pathTarget.$path );
						$map[$source]	= $pathTarget.$path.$file->file;
						break;
				}
			}
		}
		return $map;
	}

	/**
	 *	Removed installed files of module.
	 *	Does nothing if flag 'db' is set to 'only'.
	 *	@access		public
	 *	@param		object		$module			Module object
	 *	@param		boolean		$tryMode		Flag: force no changes, only try (default: no)
	 *	@return		void
	 *	@throws		RuntimeException			if target file is not readable
	 *	@throws		RuntimeException			if target file is not writable
	 */
	public function removeFiles( object $module, bool $tryMode = FALSE ): void
	{
		if( $this->flags->noFiles )
			return;
		$fileMap	= $this->prepareModuleFileMap( $module, FALSE );								//  get list of installed module files
		foreach( $fileMap as $target ){												//  iterate target file list
			if( !file_exists( $target ) && !is_link( $target ) )
				continue;
			if( !is_link( $target ) && !is_readable( $target ) )									//  if installed file is a copy and not readable
				throw new RuntimeException( 'Target file '.$target.' is not readable' );			//  throw exception
			if( !is_link( $target ) && !is_writable( $target ) )									//  if installed file is a copy and not writable
				throw new RuntimeException( 'Target file '.$target.' is not removable' );			//  throw exception
			if( !( $this->flags->dry || $tryMode ) ){												//  not a dry or try run
				@unlink( $target );																	//  remove installed file
			}
			if( $this->flags->verbose && !$this->flags->quiet && !$tryMode )						//  be verbose
				$this->client->out( '  … removed file '.$target );									//  print note about removed file
		}
	}
}
