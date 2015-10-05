<?php
class Hymn_Module_Relation{

	public $client;
	public $library;
	public $nodes	= array();

	public function __construct( Hymn_Client $client, Hymn_Module_Library $library ){
		$this->client	= $client;
		$this->library	= $library;
	}

	public function addModule( $module, $level = 0 ){
		if( !array_key_exists( $module->id, $this->nodes ) ){
			$this->nodes[$module->id]	= (object) array(
				'module'	=> $module,
				'level'		=> $level,
				'in'		=> 0,
				'out'		=> 0
			);
			foreach( $module->relations->needs as $neededModuleId ){
				$neededModule	= $this->library->getModule( $neededModuleId );
				$this->addModule( $neededModule, $level + 1 );
			}
		}
	}

	public function getOrder(){
		if( !$this->nodes )
			throw new Exception( 'No modules loaded' );
		$list	= array();
		$depth	= 0;
		foreach( $this->nodes as $id => $node )
			$depth	= max( $depth, $node->level );
		foreach( $this->nodes as $id => $node ){
			$this->nodes[$id]->out	= count( $node->module->relations->needs );
			foreach( $node->module->relations->needs as $neededModuleId )
				$this->nodes[$neededModuleId]->in	+= 1;
		}
		foreach( $this->nodes as $id => $node ){
			$a	= array(
				(string) ( $depth - $node->level ),
				(string) $node->out,
				(string) $node->in
			);
			$list[$id]	= implode( '.', $a );
		}
		asort( $list );
		$nodes	= array();
		foreach( array_keys( $list ) as $id )
			$nodes[$id]	= $this->nodes[$id]->module;
		return $nodes;
	}
}
?>
