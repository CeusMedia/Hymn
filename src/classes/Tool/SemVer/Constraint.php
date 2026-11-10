<?php
declare(strict_types=1);

class Hymn_Tool_SemVer_Constraint
{
	/** @var	string */
	public string $constraint	= '';

	/** @var	Hymn_Tool_SemVer_Constraint[] */
	public array $ors			= [];

	/** @var	Hymn_Tool_SemVer_Constraint[] */
	public array $ands			= [];

	/** @var	Hymn_Tool_SemVer_Constraint_Range|NULL */
	protected ?Hymn_Tool_SemVer_Constraint_Range $range	= NULL;

	/**
	 *	@param		string|NULL		$constraint
	 *	@throws		InvalidArgumentException	if constraint is not supported
	 */
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
		$version	= is_string( $version ) ? new Hymn_Tool_SemVer_Version( $version ) : $version;
		if( count( $this->ors ) > 0 ){
			foreach( $this->ors as $constraint )
				if( $constraint->checkVersion( $version ) )
					return TRUE;
			return FALSE;
		}
		if( count( $this->ands ) > 0 ){
			foreach( $this->ands as $constraint )
				if( !$constraint->checkVersion( $version ) )
					return FALSE;
			return TRUE;
		}
		$this->range	??= new Hymn_Tool_SemVer_Constraint_Range( $this->constraint );
		return $this->range->checkVersion( $version );
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
		return $this->ors;
	}
}
