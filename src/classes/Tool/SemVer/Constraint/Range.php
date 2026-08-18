<?php
class Hymn_Tool_SemVer_Constraint_Range
{
	/** @var	Hymn_Tool_SemVer_Version|NULL */
	protected ?Hymn_Tool_SemVer_Version $atLeast		= NULL;

	/** @var	Hymn_Tool_SemVer_Version|NULL */
	protected ?Hymn_Tool_SemVer_Version $atMost		= NULL;

	/** @var	Hymn_Tool_SemVer_Version|NULL */
	protected ?Hymn_Tool_SemVer_Version $greaterThan	= NULL;

	/** @var	Hymn_Tool_SemVer_Version|NULL */
	protected ?Hymn_Tool_SemVer_Version $lowerThan	= NULL;

	public static function create( ?string $constraint = NULL ): self
	{
		return new self( $constraint );
	}

	public function __construct( string $constraint = NULL )
	{
		if( !is_null( $constraint ) ){
			$range	= Hymn_Tool_SemVer_Constraint_Range_Parser::parse( $constraint );
			$this->setAtLeast( $range->getAtLeast() );
			$this->setAtMost( $range->getAtMost() );
			$this->setGreaterThan( $range->getGreaterThan() );
			$this->setLowerThan( $range->getLowerThan() );
		}
	}

	public function checkVersion( Hymn_Tool_SemVer_Version|string $version ): bool
	{
		$version	= is_string( $version ) ? new Hymn_Tool_SemVer_Version( $version ) : $version;
		if( !is_null( $this->atLeast ) && !is_null( $this->atMost ) ){
			if( $this->atLeast->render() === $this->atMost->render() && !$version->isEqualTo( $this->atLeast ) )
				return FALSE;
		}
		if( !is_null( $this->atLeast ) && $version->isLowerThan( $this->atLeast ) )
			return FALSE;
		if( !is_null( $this->atMost ) && $version->isGreaterThan( $this->atMost ) )
			return FALSE;
		if( !is_null( $this->greaterThan ) && $version->isAtMost( $this->greaterThan ) )
			return FALSE;
		if( !is_null( $this->lowerThan ) && $version->isAtLeast( $this->lowerThan ) )
			return FALSE;
		return TRUE;
	}

	public function getAtLeast(): ?Hymn_Tool_SemVer_Version
	{
		return $this->atLeast;
	}

	public function getAtMost(): ?Hymn_Tool_SemVer_Version
	{
		return $this->atMost;
	}

	public function getGreaterThan(): ?Hymn_Tool_SemVer_Version
	{
		return $this->greaterThan;
	}

	public function getLowerThan(): ?Hymn_Tool_SemVer_Version
	{
		return $this->lowerThan;
	}

	/**
	 *	@param		string		$constraint
	 *	@return		Hymn_Tool_SemVer_Constraint_Range
	 *	@deprecated	use Parser::parse instead
	 */
	public static function parseConstraint( string $constraint ): Hymn_Tool_SemVer_Constraint_Range
	{
		return Hymn_Tool_SemVer_Constraint_Range_Parser::parse( $constraint );
	}

	public function setAtLeast( Hymn_Tool_SemVer_Version|string|NULL $version ): self
	{
		$this->atLeast	= is_string( $version ) ? new Hymn_Tool_SemVer_Version( $version ) : $version;
		return $this;
	}

	public function setAtMost( Hymn_Tool_SemVer_Version|string|NULL $version ): self
	{
		$this->atMost	= is_string( $version ) ? new Hymn_Tool_SemVer_Version( $version ) : $version;
		return $this;
	}

	public function setGreaterThan( Hymn_Tool_SemVer_Version|string|NULL $version ): self
	{
		$this->greaterThan	= is_string( $version ) ? new Hymn_Tool_SemVer_Version( $version ) : $version;
		return $this;
	}

	public function setLowerThan( Hymn_Tool_SemVer_Version|string|NULL $version ): self
	{
		$this->lowerThan	= is_string( $version ) ? new Hymn_Tool_SemVer_Version( $version ) : $version;
		return $this;
	}
}
