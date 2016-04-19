<?php
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
	 */
	protected function prepareModuleFileMap( $module ){
		$pathSource		= $module->path;
		$pathTarget		= $this->config->application->uri;
		$theme			= isset( $this->config->layoutTheme ) ? $this->config->layoutTheme : 'custom';
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
						if( isset( $file->source ) && in_array( $file->source, $skipSources ) )
							continue;
						$path	= $this->config->paths->themes;
						$source	= $pathSource.'css/'.$file->file;
						$target	= $pathTarget.$path.$theme.'/css/'.$file->file;
						$map[$source]	= $target;
						break;
					case 'images':
						$path	= $this->config->paths->images;
						if( !empty( $file->source) && $file->source === "theme" ){
							$path	= $this->config->paths->themes;
							$path	= $path.$theme."/img/";
						}
						$source	= $pathSource.'img/'.$file->file;
						$target	= $pathTarget.$path.$file->file;
						$map[$source]	= $target;
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
