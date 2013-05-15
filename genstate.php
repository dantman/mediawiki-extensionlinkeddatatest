<?php
require 'vendor/autoload.php';
EasyRdf_Namespace::set( 'xsd', 'http://www.w3.org/2001/XMLSchema#' );
EasyRdf_Namespace::set( 'dc', 'http://purl.org/dc/terms/' );
EasyRdf_Namespace::set( 'mwe', 'http://mediawiki.org/rdf/extensions/' );

function newStateGraph( $filename, $callback ) {
	$graph = new EasyRdf_Graph( '_:self' );
	$ext = $graph->resource( null, 'mwe:ExtensionState' );
	$ext->add( 'dc:isVersionOf', $graph->resource( 'https://www.mediawiki.org/wiki/Special:MediaWikiExtension/ParserFunctions' ) );
	$ext->add( 'mwe:name', "ParserFunctions" );
	$callback( $graph, $ext );
	file_put_contents( __DIR__ . '/' . $filename, $graph->serialise( 'rdfxml' ) );
}

newStateGraph( 'extensionstate-outdated.rdf', function( $graph, $ext ) {
	$ext->add( 'mwe:gitSha1', EasyRdf_Literal_HexBinary::create( '0b1347bdd2775a9fdddcf9a746e39238d657088d', null, 'xsd:hexBinary' ) );
	$ext->add( 'mwe:versionNumber', "1.4.1" );
	$ext->add( 'dc:identifier', "1.4.1" );
} );

newStateGraph( 'extensionstate-updated.rdf', function( $graph, $ext ) {
	$ext->add( 'mwe:gitSha1', EasyRdf_Literal_HexBinary::create( 'e8905529b25d3fe572e281e5c7086c470ba2b178', null, 'xsd:hexBinary' ) );
	$ext->add( 'mwe:versionNumber', "1.5.1" );
	$ext->add( 'dc:identifier', "1.5.1" );
} );

newStateGraph( 'extensionstate-oldalpha.rdf', function( $graph, $ext ) {
	$ext->add( 'mwe:gitSha1', EasyRdf_Literal_HexBinary::create( '4367239a72b49a563c0961f09dcbbedf43db39ea', null, 'xsd:hexBinary' ) );
	$ext->add( 'dc:identifier', "alpha" );
} );
