<?php
class Hymn_Module_Installer{

	protected $client;
	protected $config;
	protected $library;
//	protected $dbc;
	protected $modulesInstalled	= array();
	protected $quiet;

	public function __construct( $client, $library, $quiet = FALSE ){
		$this->client	= $client;
		$this->config	= $this->client->getConfig();
		$this->library	= $library;
		$this->quiet	= $quiet;
//		$this->dbc		= $this->setupDatabaseConnection();
//		$this->modulesInstalled	= array();
	}

	public function configure( $module, $verbose = FALSE ){
		$source	= $module->path.'module.xml';
		$target	= $this->config->application->uri.'config/modules/'.$module->id.'.xml';
		@mkdir( dirname( $target ), 0770, TRUE );
		@copy( $source, $target );

		$xml	= file_get_contents( $target );
		$xml	= new SimpleXMLElement( $xml );
		$xml->version->addAttribute( 'install-type',  1 );
		$xml->version->addAttribute( 'install-source', $module->sourceId );
		$xml->version->addAttribute( 'install-date', date( "c" ) );

		if( isset( $this->config->modules->{$module->id}->config ) ){
			$config	= $this->config->modules->{$module->id}->config;
			foreach( $xml->config as $nr => $node ){
				if( isset( $config->{$node['name']} ) ){
					$dom = dom_import_simplexml( $node );
					$dom->nodeValue = $config->{$node['name']};
					if( $verbose && !$this->quiet )
						Hymn_Client::out( "    … configured ".$node['name'] );
				}
			}
		}
		$xml->saveXml( $target );
	}

	public function copyFiles( $module, $installType = "link", $verbose = FALSE ){
		$pathSource		= $module->path;
		$pathTarget		= $this->config->application->uri;
		$theme			= isset( $this->config->layoutTheme ) ? $this->config->layoutTheme : 'custom';
		$copy			= array();
		$skipSources	= array( 'lib', 'styles-lib', 'scripts-lib' );
		foreach( $module->files as $fileType => $files ){
			foreach( $files as $file ){
				switch( $fileType ){
					case 'files':
						$path	= $file->file;
						$copy[$pathSource.$path]	= $pathTarget.$path;
						break;
					case 'classes':
					case 'templates':
						$path	= $fileType.'/'.$file->file;
						$copy[$pathSource.$path]	= $pathTarget.$path;
						break;
					case 'locales':
						$path	= $this->config->paths->locales;
						$source	= $pathSource.'locales/'.$file->file;
						$target	= $pathTarget.$path.$file->file;
						$copy[$source]	= $target;
						break;
					case 'scripts':
						if( isset( $file->source ) && in_array( $file->source, $skipSources ) )
							continue;
						$path	= $this->config->paths->scripts;
						$source	= $pathSource.'js/'.$file->file;
						$target	= $pathTarget.$path.$file->file;
						$copy[$source]	= $target;
						break;
					case 'styles':
						if( isset( $file->source ) && in_array( $file->source, $skipSources ) )
							continue;
						$path	= $this->config->paths->themes;
						$source	= $pathSource.'css/'.$file->file;
						$target	= $pathTarget.$path.$theme.'/css/'.$file->file;
						$copy[$source]	= $target;
						break;
					case 'images':
						$path	= $this->config->paths->images;
						if( !empty( $file->source) && $file->source === "theme" ){
							$path	= $this->config->paths->themes;
							$path	= $path.$theme."/img/";
						}
						$source	= $pathSource.'img/'.$file->file;
						$target	= $pathTarget.$path.$file->file;
						$copy[$source]	= $target;
						break;
				}
			}
		}
		foreach( $copy as $source => $target ){
			@mkdir( dirname( $target ), 0770, TRUE );
			$pathNameIn = realpath( $source );
			$pathOut    = dirname( $target );
			if( $installType === "link" ){
				try{
					if( !$pathNameIn )
						throw new Exception( 'Source file '.$source.' is not existing' );
					if( !is_readable( $pathNameIn ) )
						throw new Exception( 'Source file '.$source.' is not readable' );
					if( !is_executable( $pathNameIn ) )
						throw new Exception( 'Source file '.$source.' is not executable' );
					if( !is_dir( $pathOut ) && !self::createPath( $pathOut ) )
						throw new Exception( 'Target path '.$pathOut.' is not creatable' );
					if( file_exists( $target ) ){
					//	if( !$force )
					//		throw new Exception( 'Target file '.$target.' is already existing' );
						@unlink( $target );
					}
					if( !@symlink( $source, $target ) )
						throw new Exception( 'Link of source file '.$source.' is not creatable.' );
					if( $verbose && !$this->quiet )
						Hymn_Client::out( '  … linked file '.$source );
				}
				catch( Exception $e ){
					Hymn_Client::out( 'Link Error: '.$e->getMessage().'.' );
				}
			}
			else{
				try{
					if( !$pathNameIn )
						throw new Exception( 'Source file '.$source.' is not existing' );
					if( !is_readable( $pathNameIn ) )
						throw new Exception( 'Source file '.$source.' is not readable' );
					if( !is_dir( $pathOut ) && !self::createPath( $pathOut ) )
						throw new Exception( 'Target path '.$pathOut.' is not creatable' );
					if( !@copy( $source, $target ) )
						throw new Exception( 'Source file '.$source.' could not been copied' );
					if( $verbose && !$this->quiet )
						Hymn_Client::out( '  … copied file '.$source );
				}
				catch( Exception $e ){
					Hymn_Client::out( 'Copy Error: '.$e->getMessage().'.' );
				}
			}
		}
	}

	public function install( $module, $installType = "link", $verbose = FALSE ){
		try{
			foreach( $module->relations->needs as $neededModuleId ){
				if( !in_array( $neededModuleId, $this->modulesInstalled ) ){
					$neededModule		= $this->library->getModule( $neededModuleId );
					$moduleInstallType	= $this->client->getModuleInstallType( $neededModuleId, $installType );
	//				$moduleConfig	= $this->client->getModuleConfiguration( $neededModuleId );
					$this->install( $neededModule, $moduleInstallType, $verbose );
				}
			}
			if( !$this->quiet )
				Hymn_Client::out( "- Installing module ".$module->id );
			$this->copyFiles( $module, $installType, $verbose );
			$this->configure( $module, $verbose );
			$this->runModuleInstallSql( $module, $verbose );
			$this->modulesInstalled[]	= $module->id;
			return TRUE;
		}
		catch( Exception $e ){
			throw new RuntimeException( 'Installation of module "'.$module->id.'" failed: '.$e->getMessage(), 0, $e );
		}
	}

	protected function executeSql( $sql ){
		$dbc		= $this->client->getDatabase();
		$prefix		= $this->client->getDatabaseConfiguration( 'prefix' );
		$lines		= explode( "\n", trim( $sql ) );
		$statements = array();
		$buffer		= array();
		while( count( $lines ) ){
			$line = array_shift( $lines );
			if( !trim( $line ) )
				continue;
			$buffer[]	= str_replace( "<%?prefix%>", $prefix, trim( $line ) );
			if( preg_match( '/;$/', trim( $line ) ) ){
				$statements[]	= join( "\n", $buffer );
				$buffer			= array();
			}
			if( !count( $lines ) && $buffer )
				$statements[]	= join( "\n", $buffer ).';';
		}
		foreach( $statements as $statement ){
			$result	= $dbc->exec( $statement );
			if( $result	=== FALSE )
				throw new RuntimeException( 'SQL execution failed for: '.$statement );
		}
	}

	public function runModuleInstallSql( $module, $verbose ){
		if( !$this->client->getDatabase() )
			$this->client->setupDatabaseConnection( TRUE );
		$driver	= $this->client->getDatabaseConfiguration( 'driver' );
		if( !$driver )
			throw new RuntimeException( 'Cannot install SQL of module "'.$module->id.'": No database connection available' );
		if( isset( $module->sql ) ){
			$version	= 0;
			$scripts	= array();
			foreach( $module->sql as $sql ){
				if( $sql->type === $driver || $sql->type == "*" ){
					if( $sql->event == "install" && trim( $sql->sql ) ){
						if( isset( $event->version ) )
							$version	= $sql->version = $event->version;
						$scripts[]	= trim( $sql->sql );
					}
				}
			}
			foreach( $module->sql as $sql ){
				if( $sql->type === $driver || $sql->type == "*" ){
					if( $sql->event == "update" && trim( $sql->sql ) ){
						if( isset( $event->version ) ){
							if( $event->version <= $version )
								continue;
							$version	= $sql->version = $event->version;
						}
						$scripts[]	= trim( $sql->sql );
					}
				}
			}
			foreach( $scripts as $script ){
				if( $verbose && !$this->quiet )
					Hymn_Client::out( "    … apply database script on ".$sql->event." at version ".$sql->version );
				$this->executeSql( $script );
			}
		}
	}
}
