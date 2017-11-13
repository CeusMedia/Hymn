<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2017 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Module_Graph{

	const STATUS_EMPTY		= 0;
	const STATUS_CHANGED	= 1;
	const STATUS_LINKED		= 2;
	const STATUS_PRODUCED	= 3;
	const STATUS_DRAWN		= 4;

	public $client;
	public $library;
	public $nodes		= array();
	protected $quiet	= FALSE;
	protected $status	= self::STATUS_EMPTY;

	public function __construct( Hymn_Client $client, Hymn_Module_Library $library ){
		$this->client	= $client;
		$this->library	= $library;
		$this->quiet	= $this->client->flags & Hymn_Client::FLAG_QUIET;
	}

	/**
	 *	Adds a module to graph as well as all modules linked as 'needed'.
	 *	Sets status to 'changed'.
	 *	@access		public
	 *	@param		object			$module		Module data object
	 *	@param		integer			$level		Load level of module, default: 0
	 *	@return		void
	 */
	public function addModule( $module, $level = 0 ){
		if( array_key_exists( $module->id, $this->nodes ) ){										//  module has been added already
			if( $this->nodes[$module->id]->level < $level )											//  this time the level is deeper
				$this->nodes[$module->id]->level	= $level;										//  store deeper level
			return;																					//  exit without adding relations again
		}
		$this->nodes[$module->id]	= (object) array(												//  add module to node list by module ID
			'module'	=> $module,																	//  … store module data object
			'level'		=> $level,																	//  … store load level
			'in'		=> array(),																	//  … store ingoing module links
			'out'		=> array(),																	//  … store outgoing module links
		);
		$this->status	= self::STATUS_CHANGED;														//  set internal status to "changed"
		foreach( $module->relations->needs as $neededModuleId ){									//  iterate all modules linked as "needed"
			$neededModule	= $this->library->getModule( $neededModuleId );							//  get module data object from module library
			$this->addModule( $neededModule, $level + 1 );											//  add this needed module with increased load level
		}
	}

	protected function countModuleEdgesToRoot( $node, $level = 0 ){
		$count	= $level;
		if( count( $node->in ) ){
			$ways	= array();
			foreach( $node->in as $nr => $parent )
				$ways[$nr]	= $this->countModuleEdgesToRoot( $this->nodes[$parent->id], $level + 1 );
			$count	= max( $ways );
		}
//		print( $count.' '.$node->module->id.PHP_EOL );
		return $count;
	}

	/**
	 *	Return list of all needed modules by installation order.
	 *	Calculates order key by call level and needed modules.
	 *	Resulting list will start with modules which are needed by others and end with meta modules.
	 *	@access		public
	 *	@return		array
	 */
	public function getOrder(){
		if( $this->status < self::STATUS_CHANGED )
			throw new Exception( 'No modules loaded' );
		if( $this->status < self::STATUS_LINKED )
			$this->realizeRelations( TRUE );

		/*  calculate maximum relation depth  */
		$list	= array();
		$max	= pow( 10, 8 ) - 1;
		foreach( $this->nodes as $id => $node ){
			$edges	= $this->countModuleEdgesToRoot( $node );
			$rand	= str_pad( rand( 0, $max ), 8, '0', STR_PAD_LEFT );
			$list[(float) $edges.'.'.$rand]	= $id;
		}
		krsort( $list );																				//  sort module order list

		/*  collect modules by installation order  */
		$modules	= array();																		//  prepare empty module list
		foreach( array_values( $list ) as $id )														//  iterate module order list
			$modules[$id]	= $this->nodes[$id]->module;											//  collect module by installation order
		return $modules;																			//  return list of modules by installation order
	}

	protected function realizeRelations(){
		/*  count ingoing and outgoing module links  */
		foreach( $this->nodes as $id => $node ){													//  iterate all nodes
			foreach( $node->module->relations->needs as $neededModuleId ){							//  iterate all needed modules of node
				$this->nodes[$id]->out[$neededModuleId]	= $this->nodes[$neededModuleId];			//  note outgoing link on this node
				$this->nodes[$neededModuleId]->in[$id]	= $node->module;							//  note ingoing link on the needed node
			}
		}
		$this->status	= self::STATUS_LINKED;
		if( $this->client->flags & Hymn_Client::FLAG_VERBOSE && !$this->quiet )
			Hymn_Client::out( "Found ".count( $this->nodes )." modules." );
	}

	public function renderGraphFile( $targetFile = NULL, $type = 'needs' ){							//  @todo	make indepentent from need/support
		if( $this->status < self::STATUS_LINKED )
			$this->realizeRelations();
		$nodeStyle	= 'fontsize=9 shape=box color=black style=filled color="#00007F" fillcolor="#CFCFFF"';
		$nodes	= array();
		$edges	= array();
		foreach( $this->nodes as $id => $node ){
			$label		= 'label="'.$node->module->title.'"';
			$nodes[]	= $node->module->id.' ['.$label.' '.$nodeStyle.'];';
			foreach( $node->out as $out )
				$edges[]	= $node->module->id.' -> '.$out->module->id.' []';
		}
		$options	= "\n\t".'rankdir="LR"';
		$nodes		= $nodes ? "\n\t".join( "\n\t", $nodes ) : '';
		$edges		= $edges ? "\n\t".join( "\n\t", $edges ) : '';
		$graph		= "digraph {".$options.$nodes.$edges."\n}";
		$this->status	= self::STATUS_PRODUCED;
		if( $this->client->flags & Hymn_Client::FLAG_VERBOSE && !$this->quiet )
			Hymn_Client::out( "Produced graph with ".count( $nodes )." nodes and ".count( $edges )." edged." );
		if( $targetFile ){
			file_put_contents( $targetFile, $graph );
			if( !$this->quiet )
				Hymn_Client::out( "Saved graph file to ".$targetFile."." );
		}
		return $graph;
	}

	public function renderGraphImage( $graph = NULL, $targetFile = NULL ){
		Hymn_Client::out( "Checking graphviz: ", FALSE );
		Hymn_Test::checkShellCommand( "graphviz" );
		Hymn_Client::out( "OK" );
		try{
			if( !$graph )
				$graph		= $this->renderGraphFile( NULL );
			$sourceFile	= tempnam( sys_get_temp_dir(), 'Hymn' );
			file_put_contents( $sourceFile, $graph );												//  save temporary

			if( $targetFile ){
				@exec( 'dot -Tpng -o'.$targetFile.' '.$sourceFile );
				if( !$this->quiet )
					Hymn_Client::out( 'Graph image saved to '.$targetFile.'.' );
			}
			else{
				exec( 'dot -Tpng -O '.$sourceFile );
				unlink( $sourceFile );
				$graphImage	= file_get_contents( $sourceFile.'.png' );
				@unlink( $sourceFile );
				return $graphImage;
			}
		}
		catch( Exception $e ){
			Hymn_Client::out( 'Graph rendering failed: '.$e->getMessage().'.' );
		}
	}
}
?>
