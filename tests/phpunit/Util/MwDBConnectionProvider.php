<?php

namespace SMW\Tests\Util;

use SMW\DBConnectionProvider;
use DatabaseBase;

/**
 * @ingroup Test
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class MwDBConnectionProvider implements DBConnectionProvider {

	/* @var DatabaseBase */
	protected $dbConnection = null;

	protected $connectionId;

	/**
	 * @since 1.9.3
	 *
	 * @param int $connectionId
	 */
	public function __construct( $connectionId = DB_MASTER ) {
		$this->connectionId = $connectionId;
	}

	/**
	 * @since  1.9.3
	 *
	 * @return DatabaseBase
	 */
	public function getConnection() {

		if ( $this->dbConnection === null ) {
			$this->dbConnection = wfGetDB( $this->connectionId );
		}

		return $this->dbConnection;
	}

	public function releaseConnection() {}

}
