This section details the available methods to work with documents

Getting all documents
=====================

The method **getAllDocs()** retrieve all documents from the database. In fact it only retrieve document IDs, unless you specify the server to include the documents using the [View query parameters syntax](http://github.com/dready92/PHP-on-Couch/blob/master/doc/couch_client-view.md).

Example :
    
    $all_docs = $client->getAllDocs();
    echo "Database got ".$all_docs->total_rows." documents.<BR>\n";
    foreach ( $all_docs->rows as $row ) {
        echo "Document ".$row->id."<BR>\n";
    }

Getting a document by ID
========================

The method **getDoc($id)** gives back the document that got ID $id, if it exists. Note that if the document does not exist, the method will throw an error.

The document is sent back as an HTTP object of class [stdClass](http://fr3.php.net/manual/en/reserved.classes.php).

Example :

    try {
        $doc = $client->getDoc("some_doc_id");
    } catch ( Exception $e ) {
        if ( $e->getCode() == 404 ) {
           echo "Document some_doc_id does not exist !";
	        }
        exit(1);
    }
    echo $doc->_id.' revision '.$doc->_rev;

Chainable methods to use with getDoc()
======================================

Getting a particular revision of the document
---------------------------------------------

The chainable **rev($value)** method specify the document revision to fetch.

Example :

    try {
        $doc = $client->rev("1-849aff6ad4a38b1225c80a2119dc31cb")->getDoc("some_doc_id");
    } catch ( Exception $e ) {
        if ( $e->getCode() == 404 ) {
           echo "Document some_doc_id or revision 1-849aff6ad4a38b1225c80a2119dc31cb does not exist !";
        }
        exit(1);
    }
    echo $doc->_rev ; // should echo 1-849aff6ad4a38b1225c80a2119dc31cb


Getting a document as a couchDocument object
--------------------------------------------

The **getDoc($id)** method returns a PHP stdClass object. You can however get back the document as a couchDocument object by calling the **asCouchDocuments()** method before the **getDoc($id)** method.

Example :

    try {
        $doc = $client->asCouchDocuments()->getDoc("some_doc_id");
    } catch ( Exception $e ) {
        if ( $e->getCode() == 404 ) {
           echo "Document some_doc_id does not exist !";
        }
        exit(1);
    }
    echo get_class($doc); // should echo "couchDocument"


Adding conflicts informations (if any)
--------------------------------------

The chainable method **conflicts()** asks CouchDB to add to the document a property *_conflicts* containing conflicting revisions on an object.

Example :

    try {
        $doc = $client->conflicts()->getDoc("some_doc_id");
    } catch ( Exception $e ) {
        if ( $e->getCode() == 404 ) {
           echo "Document some_doc_id does not exist !";
        }
        exit(1);
    }
    if ( $doc->_conflicts ) {
        print_r($doc->_conflicts);
    }
    

Adding revisions list
--------------------------------------

The chainable method **revs()** asks CouchDB to add to the document a property *_revisions* containing the list of revisions for an object.

Example :

    try {
        $doc = $client->revs()->getDoc("some_doc_id");
    } catch ( Exception $e ) {
        if ( $e->getCode() == 404 ) {
           echo "Document some_doc_id does not exist !";
        }
        exit(1);
    }
    print_r($doc->_revisions);

Adding revisions informations
--------------------------------------

The chainable method **revs_info()** asks CouchDB to add to the document a property *_revs_info* containing the avaibility of revisions for an object.

Example :

    try {
        $doc = $client->revs_info()->getDoc("some_doc_id");
    } catch ( Exception $e ) {
        if ( $e->getCode() == 404 ) {
           echo "Document some_doc_id does not exist !";
        }
        exit(1);
    }
    print_r($doc->_revs_info);


Fetching Several revisions of a document
========================================

Using the **open_revs( $value )** method, CouchDB returns an array of objects.

**$value** should be an array of revision ids or the special keyword all (to fetch all revisions of a document)

Example :

    try {
        $doc = $client->open_revs( array("1-fbd8a6da4d669ae4b909fcdb42bb2bfd", "2-5bc3c6319edf62d4c624277fdd0ae191") )->getDoc("some_doc_id");
    } catch ( Exception $e ) {
        if ( $e->getCode() == 404 ) {
           echo "Document some_doc_id does not exist !";
        }
        exit(1);
    }
    print_r($doc->_revs_info);

Which should return something similar to :

    array (
        stdClass(
            "missing" => "1-fbd8a6da4d669ae4b909fcdb42bb2bfd"
        ),
        stdClass(
            "ok" => stdClass(
                "_id"  => "some_doc_id",
                "_rev" => "2-5bc3c6319edf62d4c624277fdd0ae191",
                "hello"=> "foo"
            )
        )
    )

Storing a document
==================

The method **storeDoc($doc)** store a document on the CouchDB server. $doc should be an object. If the property $doc->_rev is set, the method understand that it's an update, and as so requires the property $doc->\_id to be set. If the property $doc->\_rev is not set, the method checks for the existance of property $doc->\_id and initiate the appropriate request.

The response of this method is the CouchDB server response. In other words if the request ends successfully the returned object should be :

    stdClass ( "ok" => true, "id" => "some_doc_id" , "rev" => "3-23423423476" )

Example : creating a document without specifying id

    $new_doc = new stdClass();
    $new_doc->title = "Some content";
    try {
        $response = $client->storeDoc($new_doc);
    } catch (Exception $e) {
        echo "ERROR: ".$e->getMessage()." (".$e->getCode().")<br>\n";
    }
    echo "Doc recorded. id = ".$response->id." and revision = ".$response->rev."<br>\n";
    // Doc recorded. id = 0162ff06747761f6d868c05b7aa8500f and revision = 1-249007504

Example : creating a document specifying the id

    $new_doc = new stdClass();
    $new_doc->title = "Some content";
    $new_doc->_id = "BlogPost6576";
    try {
        $response = $client->storeDoc($new_doc);
    } catch (Exception $e) {
        echo "ERROR: ".$e->getMessage()." (".$e->getCode().")<br>\n";
    }
    echo "Doc recorded. id = ".$response->id." and revision = ".$response->rev."<br>\n";
    // Doc recorded. id = BlogPost6576 and revision = 1-249004576

Example : updating an existing document :

    // get the document
    try {
        $doc = $client->getDoc('BlogPost6576');
    } catch (Exception $e) {
        echo "ERROR: ".$e->getMessage()." (".$e->getCode().")<br>\n";
    }

    // make changes
    $doc->title = 'Some smart content';
    $doc->tags = array('twitter','facebook','msn');

    // update the document on CouchDB server
    try {
        $response = $client->storeDoc($doc);
    } catch (Exception $e) {
        echo "ERROR: ".$e->getMessage()." (".$e->getCode().")<br>\n";
    }
    echo "Doc recorded. id = ".$response->id." and revision = ".$response->rev."<br>\n";
    // Doc recorded. id = BlogPost6576 and revision = 2-456769086

Updating a document
===================

Using CouchDB [Update handlers](http://wiki.apache.org/couchdb/Document_Update_Handlers), you can easily update any document part without having to send back the whole document.

Basic API
---------

The method **updateDoc( $ddoc_id, $handler_name, $params, $doc_id = null )** will try to update document according to the code defined in the update handler *$handler_name* of th design document *_design/$ddoc_id*.

Example : incrementing a document counter

Let's say we have a design document _design/myapp containing :

    "updates": {
        "bump-counter" : "function(doc, req) {
            if ( !doc ) return [null, {\"code\": 404, \"body\": \"Document not found / not specified\"}]
            if (!doc.counter) doc.counter = 0;
            doc.counter += 1;
            var message = \"<h1>bumped it!</h1>\";
            return [doc, message];
        }",
    }

To bump the counter of the document "some_doc" , use :

    $client->updateDoc("myapp","bump-counter",array(),"some_doc");


Full API
--------

The method **updateDocFullAPI($ddoc_id, $handler_name, $options)** will try to update document according to the code defined in the update handler *$handler_name* of th design document *_design/$ddoc_id*.

$options is an array of optionnal query modifiers :
"params" : array|object of variable to pass in the URL ( /?foo=bar )
"data"   : string|array|object data to set in the body of the request. If data is an array or an object it will be urlencoded using PHP http_build_query function and the request Content-Type header will be set to "application/x-www-form-urlencoded".
"Content-Type": string the Content-Type HTTP header to send to the couch server


Example :
---------

    $client->updateDocFullAPI("myapp","bump-counter",array( "data" => array("Something"=>"is set") ) );



Deleting a document
===================

The method **deleteDoc ( $doc )** permanently removes $doc from the CouchDB server. $doc should be an object containing at least \_id and \_rev properties.

Example :

    // get the document
    try {
        $doc = $client->getDoc('BlogPost6576');
    } catch (Exception $e) {
        echo "ERROR: ".$e->getMessage()." (".$e->getCode().")<br>\n";
    }
    // permanently remove the document
    try {
        $client->deleteDoc($doc);
    } catch (Exception $e) {
        echo "ERROR: ".$e->getMessage()." (".$e->getCode().")<br>\n";
    }


Copying a document
==================

The **copyDoc($id,$new_id)** method provides an handy way to copy a document. $id is the id of the document to copy. $new_id is the id of the new document.

Upon success, this method returns the CouchDB server response, which has the main form than a document storage :

    stdClass ( "ok" => true, "id" => "new_id" , "rev" => "1-23423423476" )

Example :

    try {
        $response = $client->copyDoc('BlogPost6576','CopyOfBlogPost6576');
    } catch (Exception $e) {
        echo "ERROR: ".$e->getMessage()." (".$e->getCode().")<br>\n";
    }

Attaching a file to a document
==============================

There is two methods handling attachments, it depends whether the file to send as attachment is on the harddrive, or if it's contained in a PHP variable. The first one should be more reliable for large attachments.

On-disk files to attachments
----------------------------

The method **storeAttachment($doc,$file,$content_type = 'application/octet-stream',$filename = null) ** handles the process of storing an attachment on a CouchDB document.

* **$doc** is a PHP object containing at least the properties \_id and \_rev
* **$file** is the complete path to the file on disk
* **$content_type** is the file's [content-type](http://en.wikipedia.org/wiki/MIME)
* **$filename** is the name of the attachment on CouchDB document, if the name is not the name of the file in $file

Example :

    $doc = $client->getDoc('BlogPost5676');
    $ok = $client->storeAttachment($doc,'/etc/resolv.conf','text/plain', 'my-resolv.conf');
    print_r($ok);
    // stdClass ( "ok" => true, "id" => "BlogPost5676" , "rev" => "5-2342345476" )

PHP data to attachments
----------------------------

The method **storeAsAttachment($doc,$data,$filename,$content_type = 'application/octet-stream')** records as a CouchDB document's attachment the content of a PHP variable.

* **$doc** is a PHP object containing at least the properties \_id and \_rev
* **$data** is the data (the content of the attachment)
* **$filename** is the name of the attachment on CouchDB document
* **$content_type** is the file's [content-type](http://en.wikipedia.org/wiki/MIME)

Example :

    $doc = $client->getDoc('BlogPost5676');
    $google_home=file_get_contents('http://www.google.com/');
    $ok = $client->storeAsAttachment($doc,$google_home,'text/html', 'GoogleHomepage.html');
    print_r($ok);
    // stdClass ( "ok" => true, "id" => "BlogPost5676" , "rev" => "5-2342345476" )

Delete a document attachment
============================

the method **deleteAttachment($doc,$attachment_name )** delete an attachment from a CouchDB document. $doc is an object with, at least, \_id and \_rev properties, and $attachment_name is the name of the attachment to delete.

Example :

    $doc = $client->getDoc('BlogPost5676');
    $ok = $client->deleteAttachment($doc,'GoogleHomepage.html');

Request a show view on a document
=================================

The method **getShow($design_id, $name, $doc_id = null, $additionnal_parameters = array() )** request a show formatting of document *$doc_id* with show method *$name* stored in design document *design_id*.

Example :

    $output = $client->getShow('blogs','html','BlogPost5676');

More infos on CouchDB show formatting [here](http://wiki.apache.org/couchdb/Formatting_with_Show_and_List)

Bulk operations
===============

A bulk operation is a unique query performing actions on several documents. CouchDB Bulk operations API are described in [this wiki page](http://wiki.apache.org/couchdb/HTTP_Bulk_Document_API).

Bulk documents retrieval
------------------------

To retrieve several documents in one go, knowing their IDs, select documents using the **keys($ids)** coupled with the method **getAllDocs()**. $ids is an array of documents IDs. This function acts like a view, so the output is the view output of CouchDB, and you should use "include_docs(TRUE)" to have documents contents.

Example :

    $view = $client->include_docs(true)->keys->( array('BlogPost5676','BlogComments5676') )->getAllDocs();
    foreach ( $view->rows as $row ) {
      echo "doc id :".$row->doc->_id."\n";
    }

Bulk documents storage
------------------------

To store several documents in one go, use the method **storeDocs($docs,$all_or_nothing)**. $docs is an array containing the documents to store (as couchDocuments, PHP [stdClass](http://fr3.php.net/manual/en/reserved.classes.php) or PHP arrays). $all_or_nothing is related to the updates on the database : if set to false (which is the default), all documents are saved one by one, which means that, in case of a power failure on the database, we could have some documents stored and some not stored. When set to true, couchDB will commit all documents in one go : in case of a power failure, no document will be stored, or all documents will be stored.

Example :
    $docs = array (
        array('type'=>'blogpost','title'=>'post'),
        array('type'=>'blogcomment','blogpost'=>'post','depth'=>1),
        array('type'=>'blogcomment','blogpost'=>'post','depth'=>2)
    );
    $response = $client->storeDocs( $docs );
    print_r($response);

which should give you something like :

    Array
    (
        [0] => stdClass Object
            (
                [id] => 8d7bebddc9828ed2edd052773968826b
                [rev] => 1-3988163576
            )
    
        [1] => stdClass Object
            (
                [id] => 37bcfd7d9e94c67617982527c67efe44
                [rev] => 1-1750264873
            )
    
        [2] => stdClass Object
            (
                [id] => 704a51a0b6448326152f8ffb8c3ea6be
                [rev] => 1-2477909627
            )
    
    )

This method also works to update documents.

Bulk documents removal
----------------------

To delete several documents in a single HTTP request, use the method **deleteDocs($docs,$all_or_nothing)**. $docs is an array containing the documents to store (as couchDocuments, PHP [stdClass](http://fr3.php.net/manual/en/reserved.classes.php) or PHP arrays). $all_or_nothing is related to the updates on the database : if set to false (which is the default), all documents are saved one by one, which means that, in case of a power failure on the database, we could have some documents deleted and some not deleted. When set to true, couchDB will commit all documents in one go : in case of a power failure, no document will be deleted, or all documents will be deleted.


Choosing couchClient output format
==================================

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

Example:

    $doc = $client->asArray()->getDoc('BlogPost5676');
    print_r($doc);

should print :

    Array (
        [id] => "BlogPost5676"
    )


