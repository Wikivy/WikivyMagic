<?php

namespace Wikivy\WikivyMagic\Maintenance;

use Exception;
use MediaWiki\MainConfigNames;
use MediaWiki\Maintenance\Maintenance;

class CreateCargoDB extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Creates a database for Cargo for the current wiki' );
	}

	public function execute() {
		$dbw = $this->getDB( DB_PRIMARY );
		$dbname = $this->getConfig()->get( MainConfigNames::DBname );
		if ( $dbname === null ) {
			$this->fatalError( 'Could not identify current database name!' );
		}

		$cargodb = $dbname . 'cargo';

		try {
			$dbQuotes = $dbw->addIdentifierQuotes( $cargodb );
			$dbw->query( "CREATE DATABASE $dbQuotes;", __METHOD__ );
		} catch ( Exception ) {
			$this->fatalError( "Database '$cargodb' already exists." );
		}
	}
}

// @codeCoverageIgnoreStart
return CreateCargoDB::class;
// @codeCoverageIgnoreEnd
