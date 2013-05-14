<?php
require 'vendor/autoload.php';
$graph = new EasyRdf_Graph();
$graph->parse( file_get_contents( 'test.html' ) );

echo $graph->dump( false );

// var_dump( $graph->toArray() );
