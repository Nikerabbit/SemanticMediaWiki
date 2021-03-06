<?php

if ( php_sapi_name() !== 'cli' ) {
	die( 'Not an entry point' );
}

$autoloader = require __DIR__ . '/autoloader.php';

print( "{$GLOBALS['smwgDefaultStore']}" . ( strpos( $GLOBALS['smwgDefaultStore'], 'SQL' ) ? '' : ' & ' . $GLOBALS['smwgSparqlDatabaseConnector'] ) . " ...\n\n" );

$autoloader->addPsr4( 'SMW\\Test\\', __DIR__ . '/phpunit' );
$autoloader->addPsr4( 'SMW\\Tests\\', __DIR__ . '/phpunit' );

$autoloader->addClassMap( array(
	'SMW\Tests\DataItemTest'                     => __DIR__ . '/phpunit/includes/dataitems/DataItemTest.php',
	'SMW\Tests\Reporter\MessageReporterTestCase' => __DIR__ . '/phpunit/includes/Reporter/MessageReporterTestCase.php',
	'SMW\Maintenance\RebuildConceptCache'        => __DIR__ . '/../maintenance/rebuildConceptCache.php',
	'SMW\Maintenance\RebuildData'                => __DIR__ . '/../maintenance/rebuildData.php',
	'SMW\Maintenance\RebuildPropertyStatistics'  => __DIR__ . '/../maintenance/rebuildPropertyStatistics.php',
	'SMW\Maintenance\DumpRdf'                    => __DIR__ . '/../maintenance/dumpRDF.php'
) );
