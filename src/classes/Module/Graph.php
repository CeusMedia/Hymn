<?php
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

	public function __construct( Hymn_Client $client, Hymn_Module_Library $library, $quiet = FALSE ){
		$this->client	= $client;
		$this->library	= $library;
		$this->quiet	= $quiet;
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
		if( array_key_exists( $module->id, $this->nodes ) )											//  module has been added already
			return;																					//  exit doing nothing
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
			$this->realizeRelations();

		/*  generate sortable order key by level, outgoing and ingoing module links and collect in list  */
		$list	= array();
		foreach( $this->nodes as $id => $node ){
			$a	= array(
				(string) ( $depth - $node->level ),													//  at first prefer modules which are deeply related
				(string) count( $node->out ),														//  prefer modules with less outgoing links
				(string) ( count( $this->nodes ) - count( $node->in ) )								//  prefer modules with many ingoing links
			);
			$list[$id]	= implode( '.', $a );
		}
		asort( $list );																				//  sort module order list

		/*  collect modules by installation order  */
		$modules	= array();																		//  prepare empty module list
		foreach( array_keys( $list ) as $id )														//  iterate module order list
			$modules[$id]	= $this->nodes[$id]->module;											//  collect module by installation order
		return $modules;																			//  return list of modules by installation order
	}

	public function renderGraphFile( $targetFile = NULL, $verbose = FALSE, $type = 'needs' ){						//  @todo	make indepentent from need/support
		if( $this->status < self::STATUS_LINKED )
			$this->realizeRelations( $verbose );
		$nodes	= array();
		$edges	= array();
		foreach( $this->nodes as $id => $node ){
			$style	= "";
			$label	= $node->module->title;
/*			switch( $node->status ){
				case 4:
					$style	= ' color="#7F7F00" fillcolor="#FFFFCF"';
					break;
				case 2:
					$style	= ' color="#007F00" fillcolor="#CFFFCF"';
					break;
				case 0:
					$style	= ' color="#7F0000" fillcolor="#FFCFCF"';
					break;

			}
*//*			if( count( $nodes ) ){
			}
			else
				$style	= ' fillcolor="#EFEFEF"';
*/			$nodes[]	= $node->module->id.' [label="'.$label.'" fontsize=8 shape=box color=black style=filled'.$style.'];';
			foreach( $node->out as $out )
				$edges[]	= $node->module->id.' -> '.$out->module->id.' []';
		}
		$options	= "\n\t".'rankdir="LR"';
		$nodes		= $nodes ? "\n\t".join( "\n\t", $nodes ) : '';
		$edges		= $edges ? "\n\t".join( "\n\t", $edges ) : '';
		$graph		= "digraph {".$options.$nodes.$edges."\n}";
		$this->status	= self::STATUS_PRODUCED;
		if( !$this->quiet && $verbose )
			Hymn_Client::out( "Produced graph with ".count( $nodes )." nodes and ".count( $edges )." edged." );
		if( $targetFile ){
			file_put_contents( $targetFile, $graph );
			if( !$this->quiet )
				Hymn_Client::out( "Saved graph file to ".$targetFile."." );
		}
		return $graph;
	}

	protected function realizeRelations( $verbose = FALSE ){
		/*  calculate maximum relation depth  */
		$depth	= 0;
		foreach( $this->nodes as $id => $node )
			$depth	= max( $depth, $node->level );

		/*  count ingoing and outgoing module links  */
		foreach( $this->nodes as $id => $node ){													//  iterate all nodes
			foreach( $node->module->relations->needs as $neededModuleId ){							//  iterate all needed modules of node
				$this->nodes[$id]->out[$neededModuleId]	= $this->nodes[$neededModuleId];			//  note outgoing link on this node
				$this->nodes[$neededModuleId]->in[$id]	= $node->module;							//  note ingoing link on the needed node
			}
		}
		$this->status	= self::STATUS_LINKED;
		if( !$this->quiet && $verbose )
			Hymn_Client::out( "Found ".count( $nodes )." modules and ".count( $edges )." relations." );
	}

	public function renderGraphImage( $graph = NULL, $targetFile = NULL, $verbose = FALSE ){
		Hymn_Test::checkShellCommand( "graphviz" );
		try{
			if( !$graph )
				$graph		= $this->renderGraphFile( NULL, $verbose );
			$sourceFile	= tempnam( sys_get_temp_dir(), 'Hymn' );
			file_put_contents( $sourceFile, $graph );												//  save temporary

			if( $targetFile ){
				@exec( 'dot -Tpng -o'.$targetFile.' '.$sourceFile );
				if( !$this->quiet && $verbose )
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
