<?php

namespace SMW\Tests\Integration\SQLStore;

use SMW\Tests\MwDBSQLStoreIntegrationTestCase;
use SMW\ExtensionContext;

use WikiPage;
use Title;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class RefreshSQLStoreDBIntegrationTest extends MwDBSQLStoreIntegrationTestCase {

	/* @var Title */
	protected $title;

	protected function setUp() {
		$this->runExtensionSetup( new ExtensionContext() );
		parent::setUp();
	}

	public function titleProvider() {
		$provider = array();

		$provider[] = array( NS_MAIN, 'withInterWiki', 'foo' );
		$provider[] = array( NS_MAIN, 'normalTite', '' );
		$provider[] = array( NS_MAIN, 'useUpdateJobs', '' );

		return $provider;
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testAfterPageCreation_StoreHasDataToRefreshWithoutJobs( $ns, $name, $iw ) {
		$this->title = Title::makeTitle( $ns, $name, '', $iw );

		$this->createPage( $this->title  );

		$this->assertStoreHasDataToRefresh( false );
	}

	/**
	 * @dataProvider titleProvider
	 */
	public function testAfterPageCreation_StoreHasDataToRefreshWitJobs( $ns, $name, $iw ) {
		$this->title = Title::makeTitle( $ns, $name, '', $iw );

		$this->createPage( $this->title );

		$this->assertStoreHasDataToRefresh( true );
	}

	public function tearDown() {
		if ( $this->title !== null ) {
			$this->deletePage( $this->title );
		}

		parent::tearDown();
	}

	protected function assertStoreHasDataToRefresh( $useJobs ) {
		$refreshPosition = $this->title->getArticleID();

		$refreshProgress = $this->getStore()->refreshData(
			$refreshPosition,
			1,
			false,
			$useJobs
		);

		$this->assertGreaterThan( 0, $refreshProgress );
	}

}
