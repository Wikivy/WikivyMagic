<?php

namespace Wikivy\WikivyMagic\Maintenance;


use MediaWiki\Maintenance\Maintenance;

class RemoveCustomDomain extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Removes the custom domain for the specified wiki.' );
		$this->addOption( 'dbname', 'Wiki DB name to remove the custom domain for' );

		$this->requireExtension( 'CreateWiki' );
	}

	public function execute(): void {
		$this->output( "Removing custom domain for " . $this->getOption( 'dbname' ) . "\n" );
		$moduleFactory = $this->getServiceContainer()->get( 'ManageWikiModuleFactory' );
		$mwCore = $moduleFactory->core( $this->getOption( 'dbname' ) );
		$mwCore->setServerName( "" );
		$mwCore->commit();
		$this->output( "Custom domain was sucesfully rmeoved\n" );
	}
}

// @codeCoverageIgnoreStart
return RemoveCustomDomain::class;
// @codeCoverageIgnoreEnd
