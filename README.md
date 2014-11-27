# Stanford Webservices Content Importer

@version 7.x-1.0

@author <sheamck@stanford.edu> Shea McKinney

##What does this do?

This collection of classes works with Stanford Webservices' content server.

##Requirements

A content server set up with services and UUID.

##Example Server

http://sites.stanford.edu/jsa-content

##Files

* ImporterFieldProcessor.php
	* Needs Description.
* ImporterFieldProcessorDatetime.php
	* Needs Description.
* ImporterFieldProcessorEmail.php
	* Needs Description.
* ImporterFieldProcessorFieldCollection.php
	* Needs Description.
* ImporterFieldProcessorFile.php
	* Needs Description.
* ImporterFieldProcessorImage.php
	* Needs Description.
* ImporterFieldProcessorInterface.php
	* Needs Description.
* ImporterFieldProcessorLinkField.php
	* Needs Description.
* ImporterFieldProcessorListText.php
	* Needs Description.
* ImporterFieldProcessorNumberInteger.php
	* Needs Description.
* ImporterFieldProcessorTaxonomyTermReference.php
	* Needs Description.
* ImporterFieldProcessorText.php
	* Needs Description.
* ImporterFieldProcessorTextLong.php
	* Needs Description.
* ImporterFieldProcessorTextWithSummary.php
	* Needs Description.
* SitesContentImporter.php
	* Needs Description.


##Example Usage

**Nodes:**

    // Import types
    $content_types = array(
      'stanford_page',
      'stanford_event',
    );

    // Restrictions
    // These entities we do not want even if they appear in the feed.
    $restrict = array(
      '2efac412-06d7-42b4-bf75-74067879836c',   // Recent News Page
      'fcbec50-0449-4e2d-8a79-3f957bf101e9',    // News item
      'ea1a02a9-0564-4448-82f3-09fb1d0ae8c1',   // news item
    );

    $endpoint = 'https://mysite.com/endpointname';

    $importer = new SitesContentImporter();
    $importer->set_endpoint($endpoint);
    $importer->add_import_content_type($content_types);
    $importer->add_uuid_restrictions($restrict);
    $importer->importer_content_nodes_recent_by_type();

**Vocabularies:**

    $endpoint = 'https://mysite.com/endpointname';
    $importer = new SitesContentImporter();
    $importer->set_endpoint($endpoint);
    $importer->import_vocabulary_trees();

**Beans:**

    $uuids = array(
      '67045bcc-06fc-4db8-9ef4-dd0ebb4e6d72',
      '61b6f7f7-5b94-4112-b69c-07240da330f8',
      '05f729cf-a05c-446a-96ce-324237e2a5db',
      '5ee82af2-bfac-4584-a006-a0fb0661af34',
    );

    $endpoint = 'https://mysite.com/endpointname';

    $importer = new SitesContentImporter();
    $importer->set_endpoint($endpoint);
    $importer->set_bean_uuids($uuids);
    $importer->import_content_beans();

**Nodes By Views & Filters**

    $filters = array('sites_products' => array('37'));
    $view_importer = new SitesContentImporterViews();
    $view_importer->set_endpoint($endpoint);
    $view_importer->set_resource('content');
    $view_importer->set_filters($filters);
    $view_importer->import_content_by_views_and_filters();


##Extending

You can add your own field processors by extending the ImporterFieldProcessor
class with your own. The naming convention that the processor looks for is:
ImporterFieldProcessorYourFieldNameInCamelCase
eg:
ImporterFieldProcessorFieldDateSelect


You can also register a field or property processor to run by using either:

    $importer->add_property_processor(array('property_name' => 'PHPClass'));
    $importer->add_field_processor(array('field_name' => 'PHPClass'));

PHPClass needs to be a field or property processor that extends ImporterFieldProcessor and has the process() method.

    require_once "ImporterPropertyProcessorTrimAlias.php";
    $view_importer->add_property_processor(array('url_alias' => 'ImporterPropertyProcessorTrimAlias'));


