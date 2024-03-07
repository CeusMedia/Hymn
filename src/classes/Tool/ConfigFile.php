<?php
class Hymn_Tool_ConfigFile
{
	public static function read( ?string $filePath = NULL ): Hymn_Structure_Config
	{
		$filePath	= $filePath ?? Hymn_Client::$fileName;

		if( !file_exists( $filePath ) )
			throw new RuntimeException( 'File "'.$filePath.'" is missing. Please use command "init"' );
		if( !is_readable( $filePath ) )
			throw new RuntimeException( 'File "'.$filePath.'" is not readable' );

		$content	= file_get_contents( $filePath );
		if( FALSE === $content )
			throw new RuntimeException( 'Reading file "'.$filePath.'" failed' );

		try{
			/** @var object{application: ?object, sources: ?array, paths: ?array, modules: ?array, system: ?object, database: ?object} $object */
			$object			= json_decode( $content, FALSE, 512, JSON_THROW_ON_ERROR );
		}
		/** @noinspection PhpMultipleClassDeclarationsInspection */
		catch( JsonException $e ){
/*			if( json_last_error() ){
				$message	= 'Configuration file "%s" is not valid JSON: %s';
				$this->client->outError(
					vsprintf( $message, array( $filePath, json_last_error_msg() ) ),
					Hymn_Client::EXIT_ON_RUN
				);
			}*/
			throw new RuntimeException( 'Configuration file "'.$filePath.'" is not valid JSON: '.$e->getMessage() );
		}
		return self::fromObject( $object );
	}

	public static function save( Hymn_Structure_Config $config, ?string $filePath ): int
	{
		$filePath	= $filePath ?? Hymn_Client::$fileName;

		$object		= self::fromConfig( $config );
		try{
			$json	= json_encode( $object, JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT );
		}
		catch( JsonException $e ){
/*			if( json_last_error() ){
				$message	= 'Configuration file "%s" is not valid JSON: %s';
				$this->client->outError(
					vsprintf( $message, array( $filePath, json_last_error_msg() ) ),
					Hymn_Client::EXIT_ON_RUN
				);
			}*/
			throw new RuntimeException( 'Encoding config to JSON failed: '.$e->getMessage() );
		}
		$bytes	= file_put_contents( $filePath, $json );
		if( FALSE === $bytes )
			throw new RuntimeException( 'Writing JSON to "'.$filePath.'" failed' );
		return $bytes;
	}

	//  --  PROTECTED  --  //

	/**
	 * @param object{application: ?object, sources: ?array, paths: ?array, modules: ?array, system: ?object, database: ?object} $object
	 * @return Hymn_Structure_Config
	 */
	protected static function fromObject( object $object ): Hymn_Structure_Config
	{
		$config	= new Hymn_Structure_Config();
		if( isset( $object->application ) ){
			/** @var object{title: string, url: string, uri: string, installMode: string, installType: string} $application */
			$application	= $object->application;
			$config->application->title			= $application->title ?? '';
			$config->application->url			= $application->url ?? '';
			$config->application->uri			= $application->uri ?? '';
			$config->application->installMode	= $application->installMode ?? '';
			$config->application->installType	= $application->installType ?? '';
		}
		if( isset( $object->paths ) ){
			foreach( $object->paths as $pathKey => $pathValue ){
				$config->paths->$pathKey = $pathValue;
			}
		}
		if( isset( $object->sources ) ){
			/** @var object{title: string, type: string, path: string, active: bool} $sourceData */
			foreach( $object->sources as $sourceKey => $sourceData ){
				$source	= new Hymn_Structure_Config_Source();
				$source->title	= $sourceData->title;
				$source->type	= $sourceData->type;
				$source->path	= $sourceData->path;
				$source->active	= $sourceData->active ?? true;
				$config->sources[$sourceKey]	= $source;
			}
		}
		if( isset( $object->modules ) ){
			/** @var object{sourceId: ?string, config: ?array} $moduleData */
			foreach( $object->modules as $moduleKey => $moduleData ){
				$module	= new Hymn_Structure_Config_Module();
				if( isset( $moduleData->sourceId ) )
					$module->sourceId	= $moduleData->sourceId;
				if( isset( $moduleData->config ) )
					$module->config		= $moduleData->config;
				$config->modules[$moduleKey]	= $module;
			}
		}
		if( isset( $object->system ) ){
			/** @var object{user: ?string, group: ?string} $system */
			$system	= $object->system;
			if( isset( $system->user ) )
				$config->system->user	= $system->user;
			if( isset( $system->group ) )
				$config->system->group	= $system->group;
		}
		if( isset( $object->application ) ){
			/** @var object{title: string} $application */
			$application	= $object->application;
			$config->application->title	= $application->title ?? '';
		}
		return $config;
	}

	protected static function fromConfig( Hymn_Structure_Config $config ): object
	{
		$object	= (object) [
			'application'	=> (object) get_object_vars( $config->application ),
			'sources'		=> [],
			'modules'		=> [],
		];

		foreach( $config->sources as $sourceKey => $source )
			$object->sources[$sourceKey] = get_object_vars( $source );

		foreach( $config->modules as $moduleKey => $module )
			$object->modules[$moduleKey] = get_object_vars( $module );

		if( '' !== ( $config->system->user ?? '' ) || '' !== ( $config->system->group ?? '' ) )
			$object->system		= (object) get_object_vars( $config->system );

		if( '' !== $object->database->driver )
			$object->database	= (object) get_object_vars( $config->database );

		return $object;
	}
}