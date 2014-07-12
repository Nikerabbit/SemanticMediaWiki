# SPARQLStore

The following client database connectors are currently supported:

- Jena Fuskei (`FusekiHttpDatabaseConnector`)
- Virtuoso (`VirtuosoHttpDatabaseConnector`)
- 4Store (`FourstoreHttpDatabaseConnector`)

## SPARQLStore integration

A client database connector has the responsibility to execute a query and to return a list of subjects that meet the condition.

- A `ask` query is transformed into a SPARQL condition using the `QueryConditionBuilder`
- The client database connector is to resolve the condition into a SPARQl statement and send it to the client database
- The client database (Fuseki etc. ) is expected to execute the query and return a list of raw results which is parsed by the `RawResultParser` and made available as `FederateResultList`
- The `ResultListConverter` is to create a `QueryResult` object from the `FederateResultList`
- The `QueryResult` object will fetch the remaining data (for each printrequest ) from the base store

## Integration testing

Information about the testing environment and installation details can be found [here](../../blob/master/build/travis/install-services.sh).

### Jena Fuseki integration

When running integration tests with [Jena Fuseki][fuseki] it is suggested that the `in-memory` option is used to avoid potential loss of production data during test execution.

```sh
fuseki-server --update --port=3030 --mem /db
```

```php
$smwgSparqlDatabaseConnector = 'Fuseki';
$smwgSparqlQueryEndpoint = 'http://localhost:3030/db/query';
$smwgSparqlUpdateEndpoint = 'http://localhost:3030/db/update';
$smwgSparqlDataEndpoint = '';
```

Fuseki supports [TDB Dynamic Datasets][fuseki-dataset] (in SPARQL known as [RDF dataset][sparql-dataset]) which are currently not considered for testing but can be enabled using the following settings.

```sh
fuseki-server --update --port=3030 --memTDB --set tdb:unionDefaultGraph=true /db
```
```php
$smwgSparqlDatabaseConnector = 'Fuseki';
$smwgSparqlQueryEndpoint = 'http://localhost:3030/db/query';
$smwgSparqlUpdateEndpoint = 'http://localhost:3030/db/update';
$smwgSparqlDataEndpoint = '';
$smwgSparqlDefaultGraph = 'http://example.org/myFusekiGraph';
```
### Virtuoso integration

Virtuoso-opensource 6.1

```sh
sudo apt-get install virtuoso-opensource
```

```php
$smwgSparqlDatabaseConnector = 'Virtuoso';
$smwgSparqlQueryEndpoint = 'http://localhost:8890/sparql';
$smwgSparqlUpdateEndpoint = 'http://localhost:8890/sparql';
$smwgSparqlDataEndpoint = '';
$smwgSparqlDefaultGraph = 'http://example.org/myVirtuosoGraph';
```

### 4Store integration

Currently, Travis-CI doesn't support `4Store` (1.1.4-2) as service but the following configuration has been sucessfully tested with the available test suite.

```sh
apt-get install 4store
```

```php
$smwgSparqlDatabaseConnector = '4store';
$smwgSparqlQueryEndpoint = 'http://localhost:8088/sparql/';
$smwgSparqlUpdateEndpoint = 'http://localhost:8088/update/';
$smwgSparqlDataEndpoint = 'http://localhost:8088/data/';
$smwgSparqlDefaultGraph = 'http://example.org/myFourstoreGraph';
```

[fuseki]: https://jena.apache.org/
[fuseki-dataset]: https://jena.apache.org/documentation/tdb/dynamic_datasets.html
[sparql-dataset]: https://www.w3.org/TR/sparql11-query/#specifyingDataset
