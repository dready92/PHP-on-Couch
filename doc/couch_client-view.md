This section describes how to use PHP on Couch to retrieve views results from a CouchDB server.

Creating views
==============

[As said in the documentation](http://wiki.apache.org/couchdb/HTTP_view_API) , views are stored in CouchDB documents called "design documents". So to create a view, you have to create a design document.

Example

    $view_fn="function(doc) { emit(doc.timestamp,null); }";
    $design_doc->_id = '_design/all';
    $design_doc->language = 'javascript';
    $design_doc->views = array ( 'by_date',$view_fn);
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

PHP on Couch implements chainable methods to add query parameters. The method names are mapped on their CouchDB counterparts :

* key
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

Example querying a view with a startkey, a limit and include_docs

    $response = $client->startkey(100000000)->limit(100)->include_docs(TRUE)->getView('all','by_date');

Which is the same as :

    $client->startkey(100000000);
    $client->limit(100);
    $client->include_docs(TRUE);
    $response = $client->getView('all','by_date');

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
