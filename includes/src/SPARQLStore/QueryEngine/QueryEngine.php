<?php

namespace SMW\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\Condition\Condition;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;
use SMW\SPARQLStore\QueryEngine\FederateResultList;

use SMW\Store;

use SMWSparqlDatabase as SparqlDatabase;
use SMWQueryResult as QueryResult;
use SMWQuery as Query;

/**
 * Class mapping SMWQuery objects to SPARQL, and for controlling the execution
 * of these queries to obtain suitable QueryResult objects.
 *
 * @ingroup Store
 *
 * @license GNU GPL v2+
 * @since 1.6 (updated in 1.9.3)
 *
 * @author Markus Krötzsch
 */
class QueryEngine {

	/// The name of the SPARQL variable that represents the query result.
	const RESULT_VARIABLE = 'result';

	/**
	 * @var SparqlDatabase
	 */
	private $connection;

	/**
	 * @var QueryConditionBuilder
	 */
	private $queryConditionBuilder;

	/**
	 * @var ResultListConverter
	 */
	private $resultListConverter;

	/**
	 * Copy of the SMWQuery sortkeys array to be used while building the
	 * SPARQL query conditions.
	 * @var array
	 */
	private $sortkeys;

	/**
	 * @since  1.9.3
	 *
	 * @param SparqlDatabase $connection
	 * @param QueryConditionBuilder $queryConditionBuilder
	 * @param ResultListConverter $resultListConverter
	 */
	public function __construct( SparqlDatabase $connection, QueryConditionBuilder $queryConditionBuilder, ResultListConverter $resultListConverter ) {
		$this->connection = $connection;
		$this->queryConditionBuilder = $queryConditionBuilder;
		$this->resultListConverter = $resultListConverter;

		$this->queryConditionBuilder->setResultVariable( self::RESULT_VARIABLE );
	}

	/**
	 * Get the output number for a query in counting mode.
	 *
	 * @note ignore sorting, just count
	 *
	 * @param Query $query
	 *
	 * @return integer
	 */
	public function getCountQueryResult( Query $query ) {

		// $countResultLookup = new CountResultLookup( $this->connection, $this->queryConditionBuilder );
		// $countResultLookup->getQueryResult( $query );

		$this->sortkeys = array();

		$sparqlCondition = $this->queryConditionBuilder
			->setSortKeys( $this->sortkeys )
			->buildCondition( $query->getDescription() );

		if ( $sparqlCondition instanceof SingletonCondition ) {
			if ( $sparqlCondition->condition === '' ) { // all URIs exist, no querying
				return 1;
			} else {
				$condition = $this->queryConditionBuilder->convertConditionToString( $sparqlCondition );
				$namespaces = $sparqlCondition->namespaces;
				$askQueryResult = $this->connection->ask( $condition, $namespaces );

				return $askQueryResult->isBooleanTrue() ? 1 : 0;
			}
		} elseif ( $sparqlCondition instanceof FalseCondition ) {
			return 0;
		}

		$condition = $this->queryConditionBuilder->convertConditionToString( $sparqlCondition );
		$namespaces = $sparqlCondition->namespaces;

		$options = $this->getOptions( $query, $sparqlCondition );
		$options['DISTINCT'] = true;

		$federateResultList = $this->connection->selectCount(
			'?' . self::RESULT_VARIABLE,
			$condition,
			$options,
			$namespaces
		);

		return $this->resultListConverter->convertToQueryResult( $federateResultList, $query );
	}

	/**
	 * Get the results for a query in instance retrieval mode.
	 *
	 * @param Query $query
	 *
	 * @return QueryResult
	 */
	public function getInstanceQueryResult( Query $query ) {

		$this->sortkeys = $query->sortkeys;

		$sparqlCondition = $this->queryConditionBuilder
			->setSortKeys( $this->sortkeys )
			->buildCondition( $query->getDescription() );

		if ( $sparqlCondition instanceof SingletonCondition ) {
			$matchElement = $sparqlCondition->matchElement;

			if ( $sparqlCondition->condition === '' ) { // all URIs exist, no querying
				$results = array( array ( $matchElement ) );
			} else {
				$condition = $this->queryConditionBuilder->convertConditionToString( $sparqlCondition );
				$namespaces = $sparqlCondition->namespaces;
				$askQueryResult = $this->connection->ask( $condition, $namespaces );
				$results = $askQueryResult->isBooleanTrue() ? array( array ( $matchElement ) ) : array();
			}

			$federateResultList = new FederateResultList( array( self::RESULT_VARIABLE => 0 ), $results );

		} elseif ( $sparqlCondition instanceof FalseCondition ) {
			$federateResultList = new FederateResultList( array( self::RESULT_VARIABLE => 0 ), array() );
		} else {
			$condition = $this->queryConditionBuilder->convertConditionToString( $sparqlCondition );
			$namespaces = $sparqlCondition->namespaces;

			$options = $this->getOptions( $query, $sparqlCondition );
			$options['DISTINCT'] = true;

			$federateResultList = $this->connection->select(
				'?' . self::RESULT_VARIABLE,
				$condition,
				$options,
				$namespaces
			);
		}

		return $this->resultListConverter->convertToQueryResult( $federateResultList, $query );
	}

	/**
	 * Get the output string for a query in debugging mode.
	 *
	 * @param Query $query
	 *
	 * @return string
	 */
	public function getDebugQueryResult( Query $query ) {

		$this->sortkeys = $query->sortkeys;

		$sparqlCondition = $this->queryConditionBuilder
			->setSortKeys( $this->sortkeys )
			->buildCondition( $query->getDescription() );

		$entries = array();

		if ( $sparqlCondition instanceof SingletonCondition ) {
			if ( $sparqlCondition->condition === '' ) { // all URIs exist, no querying
				$sparql = 'None (no conditions).';
			} else {
				$condition = $this->queryConditionBuilder->convertConditionToString( $sparqlCondition );
				$namespaces = $sparqlCondition->namespaces;
				$sparql = $this->connection->getSparqlForAsk( $condition, $namespaces );
			}
		} elseif ( $sparqlCondition instanceof FalseCondition ) {
			$sparql = 'None (conditions can not be satisfied by anything).';
		} else {
			$condition = $this->queryConditionBuilder->convertConditionToString( $sparqlCondition );
			$namespaces = $sparqlCondition->namespaces;

			$options = $this->getOptions( $query, $sparqlCondition );
			$options['DISTINCT'] = true;

			$sparql = $this->connection->getSparqlForSelect(
				'?' . self::RESULT_VARIABLE,
				$condition,
				$options,
				$namespaces
			);
		}

		$sparql = str_replace( array( '[',':',' ' ), array( '&#x005B;', '&#x003A;', '&#x0020;' ), $sparql );
		$entries['SPARQL Query'] = "<pre>$sparql</pre>";

		return Store::formatDebugOutput( 'SMWSparqlStore', $entries, $query );
	}

	/**
	 * Get a SPARQL option array for the given query.
	 *
	 * @param Query $query
	 * @param Condition $sparqlCondition (storing order by variable names)
	 *
	 * @return array
	 */
	protected function getOptions( Query $query, Condition $sparqlCondition ) {
		global $smwgQSortingSupport, $smwgQRandSortingSupport;

		$result = array( 'LIMIT' => $query->getLimit() + 1, 'OFFSET' => $query->getOffset() );

		// Build ORDER BY options using discovered sorting fields.
		if ( $smwgQSortingSupport ) {

			$orderByString = '';

			foreach ( $this->sortkeys as $propkey => $order ) {
				if ( ( $order != 'RANDOM' ) && array_key_exists( $propkey, $sparqlCondition->orderVariables ) ) {
					$orderByString .= "$order(?" . $sparqlCondition->orderVariables[$propkey] . ") ";
				} elseif ( ( $order == 'RANDOM' ) && $smwgQRandSortingSupport ) {
					// not supported in SPARQL; might be possible via function calls in some stores
				}
			}

			if ( $orderByString !== '' ) {
				$result['ORDER BY'] = $orderByString;
			}
		}

		return $result;
	}

}
