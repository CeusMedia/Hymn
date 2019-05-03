<?php
class Hymn_Module_Diff{

	protected $client;
	protected $config;
	protected $library;
	protected $flags;

	protected $modulesAvailable			= array();
	protected $modulesInstalled			= array();

	public function __construct( Hymn_Client $client, Hymn_Module_Library $library ){
		$this->client	= $client;
		$this->config	= $this->client->getConfig();
		$this->library	= $library;
		$this->flags	= (object) array(
			'dry'		=> $this->client->flags & Hymn_Client::FLAG_DRY,
			'quiet'		=> $this->client->flags & Hymn_Client::FLAG_QUIET,
			'verbose'	=> $this->client->flags & Hymn_Client::FLAG_VERBOSE,
		);
	}

	public function compareConfigByModules( $sourceModule, $targetModule ){
		if( !isset( $sourceModule->config ) )
			throw new InvalidArgumentException( 'Given source module object is invalid' );
		if( !isset( $targetModule->config ) )
			throw new InvalidArgumentException( 'Given target module object is invalid' );
		$skipProperties		= array( 'title', 'values', 'original', 'default' );

		$list			= array();
		$configSource	= (array) $sourceModule->config;
		$configTarget	= (array) $targetModule->config;

		foreach( $configSource as $item ){
			if( !isset( $configTarget[$item->key] ) ){
				$list[]	= (object) array(
					'status'		=> 'removed',
					'type'			=> $item->type,
					'key'			=> $item->key,
					'value'			=> $item->value,
				);
			}
		}
		foreach( $configTarget as $item ){
			if( !isset( $configSource[$item->key] ) ){
				$list[]	= (object) array(
					'status'		=> 'added',
					'type'			=> $item->type,
					'key'			=> $item->key,
					'value'			=> $item->value,
				);
			}
			else if( $item != $configSource[$item->key] ){
				$changes	= array();
				foreach( $item as $property => $value ){
					if( in_array( $property, $skipProperties ) )
						continue;
					if( !$targetModule->isInstalled && !$value )
						continue;
					$valueOld	= $value;
					if( isset( $configSource[$item->key]->{$property} ) )
						$valueOld	= $configSource[$item->key]->{$property};
					if( $valueOld !== $value ){
						$changes[]	= (object) array(
							'key'		=> $property,
							'valueOld'	=> $valueOld,
							'valueNew'	=> $value,
						);
					}
				}
				if( $changes )
					$list[]	= (object) array(
						'status'		=> 'changed',
						'type'			=> $item->type,
						'key'			=> $item->key,
						'value'			=> $item->value,
						'properties'	=> $changes,
					);
			}
		}
		return $list;
	}

	public function compareSqlByIds( $sourceModuleId, $targetModuleId ){
		$this->readModules();
		if( !array_key_exists( $sourceModuleId, $this->modulesInstalled ) )
			throw new Exception( 'Module "'.$sourceModuleId.'" is not installed' );
		if( !array_key_exists( $targetModuleId, $this->modulesAvailable ) )
			throw new Exception( 'Module "'.$targetModuleId.'" is not available' );

		$sourceModule	= $this->modulesInstalled[$sourceModuleId];
		$targetModule	= $this->modulesAvailable[$targetModuleId];
		return $this->compareSqlByModules( $sourceModule, $targetModule );
	}

	public function compareSqlByModules( $sourceModule, $targetModule ){
		$helperSql	= new Hymn_Module_SQL( $this->client );
		$scripts	= $helperSql->getModuleUpdateSql( $sourceModule, $targetModule );
		foreach( $scripts as $script )
//			$script->query	= $this->client->getDatabase()->applyTablePrefixToSql( $script->sql );
			$script->query	= $script->sql;
		return $scripts;
	}

	protected function readModules( $shelfId = NULL ){
		if( !$this->modulesAvailable ){
			$this->modulesInstalled	= $this->library->getInstalledModules( $shelfId );
			$this->modulesAvailable	= $this->library->getModules( $shelfId );
		}
	}
}
