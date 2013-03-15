lThis section describes how to use PHP on Couch to retrieve views results from a CouchDB server.

Creating views
==============

[As said in the documentation](http://wiki.apache.org/couchdb/HTTP_view_API) , views are stored in CouchDB documents called "design documents". So to create a view, you have to create a design document.

Example

    $view_fn="function(doc) { emit(doc.timestamp,null); }";
    $design_doc->_id = '_design/all';
    $design_doc->language = 'javascript';
    $design_doc->views = array ( 'by_date'=> array ('map' => $view_fn ) );
    $client->storeDoc($design_doc);

Basics of view retrieval
========================

The method **getView($id, $name)** sends back the CouchDB response of a view.
 
* **$id** is the design document id without '_design/'
* **$name** is the view name

Example :

    $result = $client->getView('all','by_date');

CouchDB response for a view
===========================

The CouchDB response of a view is an object containing :

* **total_rows** , an integer of all documents available in the view, regardless of the query options
* **offset** , an integer givving the offset between the first row of the view and the first row contained in the resultset
* **rows** an array of objects.

Each object in **rows** contains the properties :

* **id** : the id of the emited document
* **key** : the emited key
* **value** : the emited value
* **doc** : the document object, if query parameter include_docs is set (read on for that).

Query parameters
================

Using chainable methods
-----------------------

PHP on Couch implements chainable methods to add query parameters. The method names are mapped on their CouchDB counterparts :

* key
* keys
* startkey
* startkey_docid
* endkey
* endkey_docid
* limit
* stale
* descending
* skip
* group
* group_level
* reduce
* include_docs
* inclusive_end
* attachments

Example querying a view with a startkey, a limit and include_docs

    $response = $client->startkey(100000000)->limit(100)->include_docs(TRUE)->getView('all','by_date');

Which is the same as :

    $client->startkey(100000000);
    $client->limit(100);
    $client->include_docs(TRUE);
    $response = $client->getView('all','by_date');

Setting query parameters using an array
---------------------------------------

You also can set query parameters with a PHP array, using the **setQueryParameters** method :

    $opts = array ( "include_docs" => TRUE, "limit" => 10, "descending" => true );
    $response = $client->setQueryParameters(opts)->getView("all","by_date");


Choose JSON decoding option
===========================

When converting a JSON object to PHP, we can choose the type of the value returned from a couchClient query.

Take for example the following JSON object :
    { 'blog' : true, 'comments' : { 'title' : 'cool' } }

This can be converted into a PHP object :

    stdClass Object
    (
        [blog] => true
        [comments] => stdClass Object
            (
                [title] => "cool"
            )
    )


OR into a PHP array :

    Array
    (
        [blog] => true
        [comments] => Array
            (
                [title] => "cool"
            )
    )


Using the defaults, JSON objects are mapped to PHP objects. The **asArray()** method can be used to map JSON objects to PHP arrays.

Example :

    $response = $client->startkey(100000000)->limit(100)->include_docs(TRUE)->asArray()->getView('all','by_date');

Format a view with CouchDB list formatting feature
==================================================

More infos on CouchDB lists [here](http://wiki.apache.org/couchdb/Formatting_with_Show_and_List).

View and list definition in the same design document
----------------------------------------------------

The method **getList($design_id, $name, $view_name, $additionnal_parameters = array() )** retrive a view and then format it using the algorithm of the $name list.

Example :

    $response = $client->limit(100)->include_docs(TRUE)->getList('all','html','by_date');
    // will run the view declared in _design/all and named *by_date*, and then
    // pass it through the list declared in _design/all and named *html*.

View and list definition in differents design documents
-------------------------------------------------------

The method **getForeignList($list_design_id, $name, $view_design_id, $view_name, $additionnal_parameters = array() )** retrive a view 
defined in the document *_design/$view_design_id* and then format it using the algorithm of the list defined in the design document 
*_design/$list_design_id*.

Example :

    $response = $client->limit(100)->getForeignList('display','html','posts','by_date');
    // will run the view declared in _design/posts and named *by_date*, and then
    // pass it through the list declared in _design/display and named *html*.



Getting informations about a view
=================================

More info on view informations [here](http://wiki.apache.org/couchdb/HTTP_view_API#Getting_Information_about_Design_Documents_.28and_their_Views.29)

The method **getViewInfos($design_id)** sends back some useful informations about a particular design document.

Example :

    $response = $client->getViewInfos("mydesigndoc");


