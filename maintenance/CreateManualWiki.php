<?php

namespace Wikivy\WikivyMagic\Maintenance;

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Maintenance
 * @author Universal Omega
 * @version 2.0
 */

use MediaWiki\Maintenance\Maintenance;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\Platform\ISQLPlatform;

class CreateManualWiki extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Creates a wiki manually' );
		$this->addOption( 'dbname', 'The database name of the wiki to reset.', true, true );
		$this->addOption( 'requester', 'The user to assign initial rights for.', true, true );
		$this->addOption('sitename', 'The site name for the wiki', true, true );
		$this->addOption('language', 'The language of the wiki', false, true );
		$this->addOption('category', 'The category of the wiki', false, true );
		$this->addOption('dbcluster', 'Database cluster to use to create the wiki', false, true);
		$this->addOption('private', 'Should the wiki be private?');

		$this->requireExtension( 'CreateWiki' );
		$this->requireExtension( 'ManageWiki' );
	}

	public function execute(): void {
		$databaseName = strtolower( $this->getOption( 'dbname' ) );
		$requester = $this->getOption( 'requester' );
		$sitename = $this->getOption( 'sitename' );
		$language = $this->getOption( 'language', 'en' );
		$category = $this->getOption( 'category', 'Uncategorized' );
		$cluster = $this->getOption( 'dbcluster', 'c1' );
		$isPrivate = $this->getOption( 'private',  false);

		if ( !$databaseName || !$requester || !$sitename) {
			$this->fatalError( 'The options --dbname, --requester and --sitename are required.' );
		}

		$userFactory = $this->getServiceContainer()->getUserFactory();
		if ( !$userFactory->newFromName( $requester ) ) {
			$this->fatalError( "Requester '$requester' is invalid." );
		}

		$this->output( "Creating wiki: $databaseName\n" );

		// Get the load balancer for the specific cluster
		$dbLoadBalancerFactory = $this->getServiceContainer()->getDBLoadBalancerFactory();
		$loadBalancer = $dbLoadBalancerFactory->getAllMainLBs()[$cluster];

		// Get the connection to the specific cluster that we want to create the wiki on
		$dbw = $loadBalancer->getConnection( DB_PRIMARY, [], ILoadBalancer::DOMAIN_ANY );

		// Check if the database exists
		$dbExists = $dbw->newSelectQueryBuilder()
			->select( 'SCHEMA_NAME' )
			->from( 'information_schema.SCHEMATA' )
			->where( [ 'SCHEMA_NAME' => $databaseName ] )
			->caller( __METHOD__ )
			->fetchField();

		if ( $dbExists ) {
			$this->fatalError( "Database $databaseName already exists on cluster $cluster. Use ResetWiki instead." );
		}

		$dataStoreFactory = $this->getServiceContainer()->get( 'ManageWikiDataStoreFactory' );
		$wikiManagerFactory = $this->getServiceContainer()->get( 'WikiManagerFactory' );

		// Create a new WikiManagerFactory instance
		$wikiManager = $wikiManagerFactory->newInstance( $databaseName );

		// This runs checkDatabaseName and if it returns a
		// non-null value it is returning an error.
		$notCreated = $wikiManager->create(
			sitename: $sitename,
			language: $language,
			private: $isPrivate,
			category: $category,
			requester: $requester,
			extra: [],
			actor: '',
			reason: ''
		);

		if ( $notCreated ) {
			$this->fatalError( $notCreated );
		}

		$dataStore = $dataStoreFactory->newInstance( $databaseName );
		$dataStore->resetWikiData( isNewChanges: true );

		$this->output( "Wiki created successfully.\n" );
	}
}

// @codeCoverageIgnoreStart
return CreateManualWiki::class;
// @codeCoverageIgnoreEnd
