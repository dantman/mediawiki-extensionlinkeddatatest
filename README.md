MediaWiki Extension LinkedData Experiment
=========================================

To run either of the php scripts in this repository you will have to do a `{composer} install` first as these scripts depend on either EasyRDF or the JsonLD library.

## Special:MediaWikiExtension pages
Then theory is, MediaWiki.org would have Special:MediaWikiExtension pages for each installable extension that is hosted. These pages would contain up to date RDFa information about the extension that an update client could use to grab version information about the extension including a URL that can be used to install the extension.

The reason we use Special:MediaWikiExtension directly rather than using the extension's Extension: page to point to it is that this information is used for downloading and installing .php files so we can't trust the Extension: pages for that. Special:MediaWikiExtension pages would have stricter access restrictions on them.

The following files in this repo are examples of this system:

  * `test.html`: This is a html file demonstrating what the RDFa on the Special:MediaWikiExtension/ParserFunctions page for the ParserFunctions extension may look like.
  * `test.php`: This script is an example that parses the RDFa in our `test.html` file and then dumps the triples that we use on that page. While this script doesn't do anything fancy, an extension updater client would basically parse things in the same way this script does but actually start querying the graph instead of just dumping it.

## extension.jsonld metadata files
The other part of this theory is that extensions themselves would also start including a metadata file. This metadata file would be a nice JSON-LD file describing the extension that would be used by the system that manages the download hosted extensions on MediaWiki.org.

The following files in this repo are examples of this system:

  * `extension.jsonld`: This is an example metadata file demonstrating what the `extension.jsonld` for the ParserFunctions extension may look like.
  * `context.jsonld`: This file is a JSON-LD context document describing our `extension.jsonld` files. In a real system this file would live at the `https://www.mediawiki.org/rdf/extensions/extension.jsonld` url that the `extension.jsonld`'s `@context` points to.
  * `extensiontest.php`: This script is an example that parses the `extension.jsonld` and then dumps the triples used in the metadata file. To do this it has to create a modified copy if the file first since the url the `@context` points to doesn't exist yet and we need to use the `context.jsonld` file instead.

There are some interesting notes about the `extension.jsonld` file:

  * Our context file defines two special prefixes `User:` and `License:`. These point respectively to the `User:` namespace on MediaWiki.org and a `License:` namespace we would presumably create to hold referential metadata about licenses we use. This actually allows values inside of the extension definition which would normally be full urls to link to userpages and license pages using simple strings like `User:Dantman` and `License:MIT` which ironically perfectly match the MediaWiki.org titles they point to.
  * The example extension definition file defines authors somewhat verbosely using an explicit @id and schema.org schema:name (technically we almost should include a "@type": "schema:Person" in each one of them). However this isn't strictly necessary. In the future the `{ "@id": "User:Tim_Starling", "schema:name": "Tim Starling" }` block could be shortened to just `"User:Tim_Starling"` (or any other url). This would act as a url pointing to his userpage. On his userpage he could include some RDFa declaring a schema.org/Person for himself with the schema:name. Then his name and any other information he wants to declare could be grabbed from his user page instead of being embedded as a whole node in extension files (this data could even include up to date homepage and mailto links marked up with RDFa that in the future could be used to help contact extension authors).
  * The value of the license in the extension declaration is not simple text but is a rdf Resource (it could be a url that points to a resource instead of a whole node in the document). It's actually rather flexible allowing you to use multiple possible inputs:
    * You could embed an entire spdx:License node into the document to declare a license of your own.
    * You could use a url for one of the licenses defined by spdx.org at https://spdx.org/licenses/. This would have the spdx:License extracted from the RDFa that spdx.org defines at that url.
    * Since `spdx:AnyLicenseInfo` is one of the permitted types you could use a `spdx:DisjunctiveLicenseSet` node to declare a dual-licensing setup.
    * Instead of verbosely defining your `spdx:DisjunctiveLicenseSet` inside your extension definition file you could instead create a License: page on MediaWiki.org and point to that in your extension definition. This is actually how that `"License:AnyOSI"` used inside the example definition would ideally work. We would create a `[[License:AnyOSI]]` page on MediaWiki.org and mark it up with RDFa defining a `spdx:DisjunctiveLicenseSet` containing every OSI approved license as members pointing to the spdx.org licenses.
    * Instead of linking directly to an external site's license pages you could create a License page on MediaWiki.org for any license you use (even things like `License:GPLv2+`, `License:MIT`, etc...). On that page you would shortly describe that license and use a `dc:isFormatOf` to point to a more standard location defining the license in full such as the spdx licenses.
    * If we embed the licensedb.org graphs inside of our system you could also use real license urls for licenses such as `http://gnu.org/licenses/gpl-2.0.html` and `http://opensource.org/licenses/MIT` which licensedb.org has created graphs describing. These graphs even declare `spdx:licenseId` properties that can be used to loosely link these license URLs to the spdx licenses (Be warned though that using these urls for things like the GPL leaves us with no way to differentiate between things like "GPL version 2 only" and "GPL version 2 or any later version", something that the spdx license resources do allow).

## Extension state files
To allow the extension update system to understand the information about the current version of the extension it needs to do updates, archive downloads created by this system will include an extension state file containing information about the state of the extension when the archive was generated and added to the system. This file will typically be named `extensionstate.rdf`. However clients will not hardcode the filename directly and will accept any file in the extension root that matches the regexp `/^extensionstate\.[a-z][a-z0-9]*$/` (only one will be permitted to be placed in the extension). This is for future compatibility. Right now the preferred serialization for the extensionstate file is RDF/XML. But making the filename a pattern like this will allow us to change the format in the future if we decide we want to serialize it in turtle, JSON-LD, or some other format.

  * The file must contain a single anonymous `mwe:ExtensionState` resource (multiple are not permitted, the resource is found by fetching the only `? rdf:type mwe:ExtensionState` in the file).
  * This resource will include a number of properties:
    * A `dc:isVersionOf` pointing to the update resource IRI (the same one used as the `"@id"` in the JSON-LD `extension.jsonld` file). For security purposes this MUST be a https IRI.
    * A `mwe:name` with the extension name to double check the resource in the graph at the absolute IRI pointed to by the `dc:isVersionOf` is the correct extension.
    * A `mwe:gitSha1` with the sha1 of the git commit the archive was built from. This is used as a unique identifier that will work even with alpha versions. In the future we may come up with a different identifier that'll work for all VCSs and double up that common one with properties for each individual system like the `mwe:gitSha1`.
    * A `mwe:versionNumber` with the version number used if this is a normal release.
    * A `dc:identifier` with the version number used if this is a normal release or the literal string `"alpha"` for an automated alpha build.

The following files in this repo are examples of this system:

  * There are three `extensionstate-*.rdf` files depicting example extension state files that relate to the versions described by the `test.html`.
    * `extensionstate-outdated.rdf`: An example of an outdated ParserFunctions 1.4.1.
    * `extensionstate-updated.rdf`: An example of an up to date ParserFunctions 1.5.1.
    * `extensionstate-oldalpha.rdf`: An example of an alpha of ParserFunctions with an old git sha1.
  * `upgrade.php`: This script runs an example upgrade process (no actual downloading just the RDF graph stuff). It accepts one of the example extensionstate files then runs the process of checking the extension information page for updates (fetching the real graph from `test.html` instead of trying to fetch a nonexistent page).
  * `genstate.php`: This script simply generates the three extensionstate example files.
