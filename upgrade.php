<?php
require 'vendor/autoload.php';
use \ML\IRI\IRI;
EasyRdf_Namespace::set( 'xsd', 'http://www.w3.org/2001/XMLSchema#' );
EasyRdf_Namespace::set( 'dc', 'http://purl.org/dc/terms/' );
EasyRdf_Namespace::set( 'mwe', 'http://mediawiki.org/ns/extension/' );

if ( count( $argv ) >= 2 ) {
	$stateFile = $argv[1];
} else {
	echo <<<OUT
Please pass the path to an extension state file as the first argument.
We have three extensionstate-*.rdf files to try out.

OUT;
	exit(1);
}

$state = new EasyRdf_Graph();
$state->parseFile( $stateFile );
$extstate = $state->allOfType( 'mwe:ExtensionState' );
if ( count( $extstate ) == 0 ) {
	echo "The extensionstate file must define one mwe:ExtensionState, none found.\n";
	exit(1);
} elseif ( count( $extstate ) > 1 ) {
	echo "The extensionstate file must define only one mwe:ExtensionState, more than one found.\n";
	exit(1);
} else {
	$extstate = $extstate[0];
}

$stateExtname = $extstate->getLiteral( 'mwe:name' );
if ( !$stateExtname ) {
	echo "The ExtensionState must define a mwe:name with the extension's name.\n";
	exit(1);
}

$extStateVersion = $extstate->getLiteral( 'dc:identifier' );
if ( !$extStateVersion ) {
	echo "The ExtensionState must define a dc:identifier with the extension's version number or the string 'alpha'.\n";
	exit(1);
}

$ext = $extstate->getResource( 'dc:isVersionOf' );
if ( !$ext ) {
	echo "The ExtensionState must define a dc:isVersionOf pointing to the extension update information page.\n";
	exit(1);
}

$extpage = new IRI( $ext->getUri() );
if ( $extpage->getScheme() !== 'https' ) {
	echo "For security purposes extension update information pages MUST be served over HTTPS, {$extpage->getScheme()} is not permitted.\n";
	exit(1);
}

$extdata = new EasyRdf_Graph();
// In real life this would be an `$extdata->load( (string) $extpage->getAbsoluteIri() );`
// but for the demo that page is part of test.html.
$extdata->parseFile( 'test.html' );

if ( !$extdata->isA( (string) $extpage, 'mwe:Extension' ) ) {
	echo "The resource specified by dc:isVersionOf is not a mwe:Extension.\n";
	exit(1);
}

$extpageExtname = $extdata->get( (string) $extpage, 'mwe:name' );
if ( !$extpageExtname ) {
	echo "Could not find an extension name defined on the extension page.\n";
	exit(1);
}

if ( $stateExtname->getValue() !== $extpageExtname->getValue() ) {
	echo "Incorrect extension name defined on the extension page. Found data for '{$extpageExtname}' while trying to get download data for '{$stateExtname}'.\n";
	exit(1);
}

if ( $extStateVersion->getValue() === 'alpha' ) {
	$alphaVersion = $extdata->getResource( (string) $extpage, 'mwe:alphaVersion' );
	if ( !$alphaVersion->isA( 'mwe:ExtensionVersion' ) ) {
		echo "The resource {$alphaVersion->getUri()} is not a mwe:ExtensionVersion (or it does not exist).\n";
		exit(1);
	}
	$alphaVersionIdentifier = $alphaVersion->getLiteral( 'dc:identifier' );
	if ( !$alphaVersionIdentifier || $alphaVersionIdentifier->getValue() !== 'alpha' ) {
		echo "The alpha version dc:identifier must be set to the string 'alpha'. Identifier is either undefined or not set to alpha.\n";
		exit(1);
	}
	$upgradeTo = $alphaVersion;
} else {
	$stableVersion = $extdata->getResource( (string) $extpage, 'mwe:stableVersion' );
	$upgradeTo = $stableVersion;
	// $stableVersionIdentifier = $stableVersion->getLiteral( 'dc:identifier' );
	// if ( !$stableVersionIdentifier ) {
	// 	echo "The stable version dc:identifier was not set.\n";
	// 	exit(1);
	// }

	$version = $stableVersion;
	$versionCount = 0;
	$versionsTested = array();
	while(1) {
		if ( array_key_exists( $version->getUri(), $versionsTested ) ) {
			echo "Version loop detected {$version->getUri()} was marked as a previousVersion but was already fetched as a previousVersion of a newer version of the extension.\n";
			exit(1);
		}
		$versionsTested[$version->getUri()] = true;
		if ( !$version->isA( 'mwe:ExtensionVersion' ) ) {
			echo "The resource {$version->getUri()} is not a mwe:ExtensionVersion (or it does not exist).\n";
			exit(1);
		}
		$versionIdentifier = $version->getLiteral( 'dc:identifier' );
		if ( !$versionIdentifier ) {
			echo "The version dc:identifier for {$version->getUri()} was not set.\n";
			exit(1);
		}
		if ( $extStateVersion->getValue() == $versionIdentifier->getValue() ) {
			if ( $versionCount > 0 ) {
				echo "There have been {$versionCount} releases since you upgraded/installed this extension.\n";
			}
			break;
		}
		$version = $version->getResource( 'mwe:previousVersion' );
		if ( !$version ) {
			echo "The version you have of this extension is not listed on the extension page.\n";
			break;
		}
		$versionCount++;
	}
}
if ( $upgradeTo->getLiteral( 'dc:identifier' )->getValue() == $extStateVersion->getValue() ) {
	$stateGitSha1 = $extstate->getLiteral( 'mwe:gitSha1' );
	$upgradeGitSha1 = $upgradeTo->getLiteral( 'mwe:gitSha1' );
	if ( !$stateGitSha1 ) {
		echo "Could not find a mwe:gitSha1 on the extension state.\n";
		exit(1);
	}
	if ( !$upgradeGitSha1 ) {
		echo "Could not find a mwe:gitSha1 on the version to upgrade to.\n";
		exit(1);
	}
	if ( strtolower( $stateGitSha1->getValue() ) == strtolower( $upgradeGitSha1->getValue() ) ) {
		echo "Your're completely up to date for this extension.\n";
		exit(1);
	} else {
		echo "Your version number matches the most up to date version however the git sha1 is different indicating some changes to the extension since you downloaded it.\n";
	}
}

$archiveUrl = $upgradeTo->getResource( 'mwe:archiveUrl' );
if ( $archiveUrl ) {
	echo "The download url is:\n";
	echo "> {$archiveUrl->getUri()}\n";
}
