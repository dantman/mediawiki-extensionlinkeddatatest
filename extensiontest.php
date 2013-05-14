<?php
require 'vendor/autoload.php';
use ML\JsonLD as LD;

# In the real world https://www.mediawiki.org/rdf/extensions/extension.jsonld will
# be a real .jsonld document. However right now it's not and hence to parse this
# right we need to copy our extension metadata file to a new location and modify
# it to point the @context to a local file for now.

$tempfile = tempnam( "/tmp", "mwextensionldtest-" );
$jsonld = json_decode( file_get_contents( __DIR__ . '/extension.jsonld' ) );
$jsonld->{'@context'} = 'context.jsonld';
file_put_contents( $tempfile, json_encode( $jsonld ) );

# Now actually parse our jsonld file and dump a representation of the graph.
$flattened = LD\JsonLD::flatten($tempfile);
$doc = LD\JsonLD::getDocument($flattened);
$graph = $doc->getGraph();
$nodes = $graph->getNodes();

echo "Graph:\n";
foreach ( $nodes as $node ) {
	echo $node->getId() . " (Node)\n";
	foreach ( $node->getProperties() as $property => $values ) {
		if ( !is_array( $values ) ) {
			$values = array( $values );
		}
		foreach ( $values as $value ) {
			if ( $value instanceof LD\LanguageTaggedString ) {
				echo "	-> $property -> " . var_export( $value->getValue(), true ) . "@{$value->getLanguage()}\n";
			} elseif ( $value instanceof LD\Node ) {
				echo "	-> $property -> " . $value->getId() . "\n";
			} elseif ( is_string( $value ) ) {
				echo "	-> $property -> " . var_export( $value, true ) . "\n";
			} else {
				echo get_class( $value ) . "\n";
			}
		}
	}
	echo "\n";
}

unlink($tempfile);

// $quads = LD\JsonLD::toRdf('extension.json');
// $nquads = new NQuads();
// $serialized = $nquads->serialize($quads);
// print $serialized;
