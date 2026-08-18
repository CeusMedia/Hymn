<?php


class Hymn_Tool_SemVer_Constraint
{
	/** @var	string */
	public string $constraint	= '';

	/** @var	Hymn_Tool_SemVer_Constraint[] */
	public array $ors			= [];

	/** @var	Hymn_Tool_SemVer_Constraint[] */
	public array $ands			= [];

	public function __construct( ?string $constraint = NULL )
	{
		if( NULL !== $constraint ){
			$object	= Hymn_Tool_SemVer_Constraint_Parser::parse( $constraint );
			$this->ors			= $object->ors;
			$this->ands			= $object->ands;
			$this->constraint	= $object->constraint;
		}
	}

	public function checkVersion( Hymn_Tool_SemVer_Version|string $version ): bool
	{
		if( count( $this->ands ) > 0 ){
			$valid	= TRUE;
			foreach( $this->ands as $constraint )
				$valid	= $valid && $constraint->checkVersion( $version );
			return $valid;
		}
		else if( count( $this->ors ) > 0 ){
			$valid	= FALSE;
			foreach( $this->ors as $constraint )
				$valid	= $valid || $constraint->checkVersion( $version );
			return $valid;
		}
		else{
			$range	= new Hymn_Tool_SemVer_Constraint_Range( $this->constraint );
			return $range->checkVersion( $version );
		}
	}

	/**
	 *	@return		Hymn_Tool_SemVer_Constraint[]
	 */
	public function getAnds(): array
	{
		return $this->ands;
	}

	/**
	 *	@return		string
	 */
	public function getConstraint(): string
	{
		return $this->constraint;
	}

	/**
	 *	@return		Hymn_Tool_SemVer_Constraint[]
	 */
	public function getOrs(): array
	{
		return $this->ands;
	}
}
