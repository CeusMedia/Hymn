<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2016 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Module_Files{

	protected $client;
	protected $config;
	protected $quiet;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		object		$client		Hymn client instance
	 *	@param		boolean		$quiet		Flag: be quiet and ignore verbosity
	 */
	public function __construct( $client, $quiet = FALSE ){
		$this->client	= $client;
		$this->config	= $this->client->getConfig();
		$this->quiet	= $quiet;
	}

	/**
	 *	Tries to link or copy all module files into application.
	 *	@access		public
	 *	@param 		object 		$module			Module object
	 *	@param		string		$installType	One of {link, copy}
	 *	@param		boolean		$verbose		Flag: be verbose during processing
	 *	@param		boolean		$dry			Flag: dry run mode - simulation only
	 *	@return		void
	 *	@throws		Exception	if any file manipulation action goes wrong
	 */
	public function copyFiles( $module, $installType = "link", $verbose = FALSE, $dry = FALSE ){
		$fileMap	= $this->prepareModuleFileMap( $module );
		foreach( $fileMap as $source => $target ){
			@mkdir( dirname( $target ), 0770, TRUE );
			$pathNameIn	= realpath( $source );
			$pathOut	= dirname( $target );
			if( $installType === "link" ){
//				try{
					if( !$pathNameIn )
						throw new Exception( 'Source file '.$source.' is not existing' );
					if( !is_readable( $pathNameIn ) )
						throw new Exception( 'Source file '.$source.' is not readable' );
					if( !is_executable( $pathNameIn ) )
						throw new Exception( 'Source file '.$source.' is not executable' );
					if( !is_dir( $pathOut ) && !self::createPath( $pathOut ) )
						throw new Exception( 'Target path '.$pathOut.' is not creatable' );
					if( !$dry ){																	//  not a dry run
						if( file_exists( $target ) ){
						//	if( !$force )
						//		throw new Exception( 'Target file '.$target.' is already existing' );
								@unlink( $target );
						}
						if( !@symlink( $source, $target ) )
							throw new Exception( 'Link of source file '.$source.' is not creatable.' );
					}
					if( $verbose && !$this->quiet )
						Hymn_Client::out( '  … linked file '.$source );
//				}
//				catch( Exception $e ){
//					Hymn_Client::out( 'Link Error: '.$e->getMessage().'.' );
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
					if( !$dry ){																	//  not a dry run
						if( !@copy( $source, $target ) )											//  copying failed
							throw new Exception( 'Source file '.$source.' could not been copied' );
					}
					if( $verbose && !$this->quiet )
						Hymn_Client::out( '  … copied file '.$source );
//				}
//				catch( Exception $e ){
//					throw new Exception( 'Copy Error: '.$e->getMessage().'.' );
//				}
			}
		}
		return TRUE;
	}

	/**
	 *	Enlist all module files onto a map of source and target files.
	 *	@access		protected
	 *	@param 		object 		$module		Module object
	 *	@return		array
	 *	@todo   	change behaviour of styles without source: install into common instead of theme
	 */
	protected function prepareModuleFileMap( $module ){
		$pathSource		= $module->path;
		$pathTarget		= $this->config->application->uri;
		$layoutTheme	= isset( $this->config->layoutTheme ) ? $this->config->layoutTheme : 'common';
		$layoutPrimer	= isset( $this->config->layoutPrimer ) ? $this->config->layoutPrimer : 'primer';
		$map			= array();
		$skipSources	= array( 'lib', 'styles-lib', 'scripts-lib', 'url' );
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
							continue;
						$path	= $this->config->paths->scripts;
						$source	= $pathSource.'js/'.$file->file;
						$target	= $pathTarget.$path.$file->file;
						$map[$source]	= $target;
						break;
					case 'styles':
						if( !isset( $file->source ) )
							$file->source	= 'theme';
						if( in_array( $file->source, $skipSources ) )
							continue;
						switch( $file->source ){
							case 'styles-lib':
							case 'scripts-lib':
								continue;
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
						if( !file_exists( $path ) )
							mkdir( $path, 0777, TRUE );
						$source	= $pathSource.'css/'.$file->file;
						$map[$source]	= $path.'/css/'.$file->file;
						break;
					case 'images':
						$source	= $pathSource.'img/'.$file->file;
						if( !isset( $file->source ) )
							$file->source	= 'images';
						switch( $file->source ){
							case 'styles-lib':
							case 'scripts-lib':
								continue;
							case 'common':
								$path	= $this->config->paths->themes.'common/';
								break;
							case 'primer':
								$path	= $this->config->paths->themes.$layoutPrimer.'/';
								break;
							case 'theme':
								$theme	= !empty( $file->theme ) ? $file->theme : $layoutTheme;
								$path	= $this->config->paths->themes.$theme.'/';
								break;
							case 'images':
							default:
								$path	= $this->config->paths->images;
								break;
						}
						if( !file_exists( $pathTarget.$path ) )
							mkdir( $pathTarget.$path, 0777, TRUE );
						$map[$source]	= $pathTarget.$path.$file->file;
						break;
				}
			}
		}
		return $map;
	}

	/**
	 *	Removed installed files of module.
	 *	@access		public
	 *	@param 		object 		$module			Module object
	 *	@param 		boolean 	$verbose		Flag: be verbose
	 *	@param		boolean		$dry			Flag: dry run mode - simulation only
	 *	@return		void
	 *	@throws		RuntimeException			if target file is not readable
	 *	@throws		RuntimeException			if target file is not writable
	 */
	public function removeFiles( $module, $verbose = FALSE, $dry =  FALSE ){
		$fileMap	= $this->prepareModuleFileMap( $module );										//  get list of installed module files
		foreach( $fileMap as $source => $target ){													//  iterate file list
			if( !is_readable( $target ) )															//  if installed file is not readable
				throw new RuntimeException( 'Target file '.$target.' is not readable' );			//  throw exception
			if( !is_writable( $target ) )															//  if installed file is not writable
				throw new RuntimeException( 'Target file '.$target.' is not removable' );			//  throw exception
			if( !$dry ){																			//  not a dry run
				@unlink( $target );																	//  remove installed file
			}
			if( $verbose && !$this->quiet )															//  be verbose
				Hymn_Client::out( '  … removed file '.$target );									//  print note about removed file
		}
	}
}
