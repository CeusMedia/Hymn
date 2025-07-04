<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2025 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Module_Graph
{
	public const STATUS_EMPTY		= 0;
	public const STATUS_CHANGED		= 1;
	public const STATUS_LINKED		= 2;
	public const STATUS_PRODUCED	= 3;
	public const STATUS_DRAWN		= 4;

	/** @var		Hymn_Client				$client */
	public Hymn_Client $client;

	/** @var		Hymn_Module_Library		$library */
	public Hymn_Module_Library $library;

	/** @var		array					$nodes */
	public array $nodes						= [];

	/** @var		object{quiet: bool, verbose: bool}	$flags */
	protected object $flags;

	/** @var		integer					$status */
	protected int $status					= self::STATUS_EMPTY;

	public function __construct( Hymn_Client $client, Hymn_Module_Library $library )
	{
		$this->client	= $client;
		$this->library	= $library;
		$this->flags	= (object) [
			'quiet'		=> (bool) ($this->client->flags & Hymn_Client::FLAG_QUIET ),
			'verbose'	=> (bool) ($this->client->flags & Hymn_Client::FLAG_VERBOSE ),
		];
	}

	/**
	 *	Adds a module to graph as well as all modules linked as 'needed'.
	 *	Sets status to 'changed'.
	 *	@access		public
	 *	@param		Hymn_Structure_Module	$module		Module data object
	 *	@param		integer					$level		Load level of module, default: 0
	 *	@return		void
	 */
	public function addModule( Hymn_Structure_Module $module, int $level = 0 ): void
  {
//		if( version_compare( $this->client->getFramework()->getVersion(), '0.8.8.2', '<' ) )		//  framework is earlier than 0.8.8.2
//			$module	= $this->library->getAvailableModule( $module->id );							//  load module using library

		if( array_key_exists( $module->id, $this->nodes ) ){										//  module has been added already
			if( $this->nodes[$module->id]->level < $level )											//  this time the level is deeper
				$this->nodes[$module->id]->level	= $level;										//  store deeper level
			return;																					//  exit without adding relations again
		}
		$this->nodes[$module->id]	= (object) [													//  add module to node list by module ID
			'module'	=> $module,																	//  … store module data object
			'level'		=> $level,																	//  … store load level
			'in'		=> [],																		//  … store ingoing module links
			'out'		=> [],																		//  … store outgoing module links
		];
		$this->status	= self::STATUS_CHANGED;														//  set internal status to "changed"
		foreach( $module->relations->needs as $neededModuleId => $relation ){						//  iterate all modules linked as "needed"
			//	 @todo remove this block after framework v0.8.8.2 is established
			if( is_string( $relation ) ){															//  relation came from a reduced module source index
				$neededModuleId	= $relation;														//  relation only holds module ID
				$relation		= (object) [														//  simulate relation object
					'type'		=> str_contains( $relation, '/' ) ? 'package' : 'module',			//  detect packages and modules
					'source'	=> NULL,
				];
			}
			if( Hymn_Structure_Module_Relation::TYPE_MODULE !== $relation->type )
			 	continue;
			if( $relation->source ){
				if( !$this->library->isAvailableModuleInSource( $neededModuleId, $relation->source ) ){
					$message	= 'Module %s needs module %s from source %s, which is missing.';
					$this->client->outError( vsprintf( $message, [
						$module->id,
						$neededModuleId,
						$relation->source,
					] ), Hymn_Client::EXIT_ON_RUN );
				}
			}
			$neededModule	= $this->library->getAvailableModule( $neededModuleId, $relation->source );		//  get module data object from module library
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
	public function getOrder(): array
	{
		if( $this->status < self::STATUS_CHANGED )
			throw new RuntimeException( 'No modules loaded' );
		if( $this->status < self::STATUS_LINKED )
			$this->realizeRelations();

		/*  calculate maximum relation depth  */
		$list	= [];
		$max	= pow( 10, 8 ) - 1;
		foreach( $this->nodes as $id => $node ){
//			if( $this->flags->verbose && !$this->flags->quiet )
//				$this->client->out( 'Check for loop: '.$node->module->id.' @ '.$node->module->sourceId );
			$loop	= $this->checkForLoop( $node );
			if( $loop ){
				$this->client->outError( 'Module relation Loop found in module '.$loop->module->id.' @ '.$loop->module->sourceId );
				foreach( array_values( $loop->modules ) as $nr => $item ){
					$bullet	= str_pad( (string) ++$nr, 3, ' ', STR_PAD_LEFT );
					$this->client->out( ' '.$bullet.'. '.$item->id.' @ '.$item->sourceId );
				}
				$this->client->outError( 'Please resolve loop, first!', Hymn_Client::EXIT_ON_RUN );
			}
			$edges	= $this->countModuleEdgesToRoot( $node );
			$rand	= str_pad( (string) rand( 0, $max ), 8, '0', STR_PAD_LEFT );
			$list[(float) $edges.'.'.$rand]	= $id;
		}
		krsort( $list );																			//  sort module order list

		/*  collect modules by installation order  */
		$modules	= [];																		//  prepare empty module list
		foreach( $list as $id )														//  iterate module order list
			$modules[$id]	= $this->nodes[$id]->module;											//  collect module by installation order
		return $modules;																			//  return list of modules by installation order
	}

	//  @todo	make independent from need/support
	public function renderGraphFile( string $targetFile = NULL/*, string $type = 'needs'*/ ): string
	{
		if( $this->status < self::STATUS_LINKED )
			$this->realizeRelations();
		$nodeStyle	= 'fontsize=9 shape=box color=black style=filled color="#00007F" fillcolor="#CFCFFF"';
		$nodes	= [];
		$edges	= [];
		foreach( $this->nodes as $id => $node ){
			$label		= 'label="'.$node->module->title.'"';
			$nodes[]	= $node->module->id.' ['.$label.' '.$nodeStyle.'];';
			foreach( $node->out as $out )
				$edges[]	= $node->module->id.' -> '.$out->module->id.' []';
		}
		$options	= "\n\t".'rankdir="LR"';
		$this->status	= self::STATUS_PRODUCED;
		if( $this->flags->verbose && !$this->flags->quiet )
			$this->client->out( "Produced graph with ".count( $nodes )." nodes and ".count( $edges )." edged." );
		$nodes		= $nodes ? "\n\t".join( "\n\t", $nodes ) : '';
		$edges		= $edges ? "\n\t".join( "\n\t", $edges ) : '';
		$graph		= "digraph {".$options.$nodes.$edges."\n}";
		if( $targetFile ){
			file_put_contents( $targetFile, $graph );
			if( !$this->flags->quiet )
				$this->client->out( "Saved graph file to ".$targetFile."." );
		}
		return $graph;
	}

	public function renderGraphImage( ?string $graph = NULL, ?string $targetFile = NULL ): ?string
	{
		$this->client->out( "Checking graphviz: ", FALSE );
		$toolTest	= new Hymn_Tool_Test( $this->client );
		$toolTest->checkShellCommand( "graphviz" );
		$this->client->out( "OK" );
		try{
			if( !$graph )
				$graph		= $this->renderGraphFile();
			$sourceFile	= tempnam( sys_get_temp_dir(), 'Hymn' );
			file_put_contents( $sourceFile, $graph );												//  save temporary

			if( $targetFile ){
				@exec( 'dot -Tpng -o'.$targetFile.' '.$sourceFile );
				if( !$this->flags->quiet )
					$this->client->out( 'Graph image saved to '.$targetFile.'.' );
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
			$this->client->out( 'Graph rendering failed: '.$e->getMessage().'.' );
		}
		return NULL;
	}

	/**
	 *	Check for loop in module relations.
	 *	@access		protected
	 *	@param		object		$node		Node data object containing module and in and out relations
	 *	@param		integer		$level		Counter of recursion level, 0 by default.
	 *	@return		object|NULL				Object if looping node or null if no loop found
	 */
	protected function checkForLoop( object $node, int $level = 0, array $steps = [] ): ?object
  {
		if( array_key_exists( $node->module->install->path, $steps ) )								//  been in this module in before
			return (object) array(																	//  return loop data ...
				'module'	=> $node->module,														//  ... containing looping module
				'modules'	=> $steps																//  ... and the module chain
			);
		$steps[$node->module->install->path]	= $node->module;									//  note this module in module chain
		if( count( $node->in ) ){																	//  there are relations
			foreach( $node->in as $parent ){														//  iterate these relations
				$parent	= $this->nodes[$parent->id];												//  shortcut parent module
				$loop	= $this->checkForLoop( $parent, $level + 1, $steps );						//  recurse for related node
				if( $loop )																			//  found loop
					return $loop;																	//  return looping node to prior rounds
			}
		}
		return NULL;																				//  no loop found
	}

	protected function countModuleEdgesToRoot( object $node, int $level = 0 ): int
	{
		$count	= $level;
		if( count( $node->in ) ){
			$ways	= [];
			foreach( $node->in as $parent )
				$ways[]	= $this->countModuleEdgesToRoot( $this->nodes[$parent->id], $level + 1 );
			$count	= max( $ways );
		}
		return $count;
	}

	protected function realizeRelations(): void
	{
		/*  count ingoing and outgoing module links  */
		foreach( $this->nodes as $id => $node ){													//  iterate all nodes
			foreach( $node->module->relations->needs as $neededModuleId => $relation ){				//  iterate all needed modules of node
				//	 @todo remove this block after framework v0.8.8.2 is established
				if( is_string( $relation ) ){														//  relation came from a reduced module source index
					$neededModuleId	= $relation;													//  relation only holds module ID
					$relation		= (object) [													//  simulate relation object
						'type'		=> str_contains( $relation, '/' ) ? 'package' : 'module',		//  detect packages and modules
						'source'	=> NULL,
					];
				}
				if( Hymn_Structure_Module_Relation::TYPE_MODULE === $relation->type ){
					$this->nodes[$id]->out[$neededModuleId]	= $this->nodes[$neededModuleId];		//  note outgoing link on this node
					$this->nodes[$neededModuleId]->in[$id]	= $node->module;						//  note ingoing link on the needed node
				}
			}
		}
		$this->status	= self::STATUS_LINKED;
		if( $this->flags->verbose && !$this->flags->quiet )
			$this->client->outVeryVerbose( "Found ".count( $this->nodes )." modules." );
	}
}
