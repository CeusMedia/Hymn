<?php
class Hymn_Module_Installer{

	protected $client;
	protected $config;
	protected $library;
//	protected $dbc;
	protected $modulesInstalled	= array();

	public function __construct( $client, $library ){
		$this->client	= $client;
		$this->config	= $this->client->getConfig();
		$this->library	= $library;
//		$this->dbc		= $this->setupDatabaseConnection();
//		$this->modulesInstalled	= array();
	}

	public function configure( $module ){
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
				}
			}
		}
		$xml->saveXml( $target );
	}

	public function copyFiles( $module ){
		$pathSource	= $module->path;
		$pathTarget	= $this->config->application->uri;
		$configApp	= $this->config->application->config;
		$copy		= array();
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
						$path	= isset( $configApp->pathLocales ) ? $configApp->pathLocales : "locales/";
						$source	= $pathSource.'locales/'.$file->file;
						$target	= $pathTarget.$path.$file->file;
						$copy[$source]	= $target;
						break;
					case 'scripts':
						$path	= isset( $configApp->pathScripts ) ? $configApp->pathScripts : "javascripts/";
						$source	= $pathSource.'js/'.$file->file;
						$target	= $pathTarget.$path.$file->file;
						$copy[$source]	= $target;
						break;
					case 'styles':
						$theme	= "custom";
						$path	= isset( $configApp->pathThemes ) ? $configApp->pathThemes : "themes/";
						$source	= $pathSource.'css/'.$file->file;
						$target	= $pathTarget.$path.$theme.'/css/'.$file->file;
						$copy[$source]	= $target;
						break;
					case 'images':
						$theme	= "custom";
						$path	= isset( $configApp->pathImages ) ? $configApp->pathImages : "images/";
						if( !empty( $file->source) && $file->source === "theme" ){
							$path	= isset( $configApp->pathThemes ) ? $configApp->pathThemes : "themes/";
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
			@copy( $source, $target );
		}
	}

	public function install( $module, $installType = "link" ){
		try{
			foreach( $module->relations->needs as $neededModuleId ){
				if( !in_array( $neededModuleId, $this->modulesInstalled ) ){
					$neededModule		= $this->library->getModule( $neededModuleId );
					$moduleInstallType	= $this->client->getModuleInstallType( $neededModuleId );
	//				$moduleConfig	= $this->client->getModuleConfiguration( $neededModuleId );
					$this->install( $neededModule, $moduleInstallType );
				}
			}
			Hymn_Client::out( "- Installing module ".$module->id, FALSE );
			$this->copyFiles( $module, $installType );
			$this->configure( $module );
			$this->runModuleInstallSql( $module );
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

	public function runModuleInstallSql( $module ){
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
							$version	= $event->version;
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
							$version	= $event->version;
						}
						$scripts[]	= trim( $sql->sql );
					}
				}
			}
			foreach( $scripts as $script ){
				$this->executeSql( $script );
			}
		}
	}
}
