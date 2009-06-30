This section details the available methods to work with documents

Getting all documents
=====================

The method **get_all_docs()** retrieve all documents from the database. In fact it only retrieve document IDs, unless you specify the server to include the documents using the [View query parameters syntax](http://dready.byethost31.com/index.php/display/view/196).

Example :
    
    $all_docs = $client->get_all_docs();
    echo "Database got ".$all_docs->total_rows." documents.<BR>\n";
    foreach ( $all_docs->rows as $row ) {
        echo "Document ".$row->id."<BR>\n";
    }

Getting documents by update sequence
====================================

The method **get_all_docs_by_seq()** retrieval of actions on the database server : whenever a document is stored or deleted, CouchDB updates a sequence number and record the action.

Example :

    print_r($client->get_all_docs_by_seq());
    /*
    stdClass ( "total_rows" => 4, "offset" => 0, "rows" => array (
        stdClass ( "id" => "doc1", "key" => "1", "value"=> stdClass ("rev" =>"1-4124667444")),
        stdClass ("id" => "doc2", "key" => "2", "value" => stdClass ("rev"=>"1-1815587255")),
        stdClass("id" => "doc3", "key" => "3", "value" => stdClass ( "rev" => "1-1750227892")),
        stdClass("id" => "doc4", "key" => "4", "value" => stdClass ( "rev" =>"2-524044848", "deleted" => true))
    ))
    */

Getting a document by ID
========================

The method **doc_get($id)** gives back the document that got ID $id, if it exists. Note that if the document does not exist, the method will throw an error.

The document is sent back as an HTTP object of class [stdClass](http://fr3.php.net/manual/en/reserved.classes.php).

Example :

    try {
        $doc = $client->doc_get("some_doc_id");
    } catch ( Exception $e ) {
        if ( $e->getCode() == 404 ) {
           echo "Document some_doc_id does not exist !";
        }
    }
    echo $doc->_id.' revision '.$doc->_rev;

Storing a document
==================

The method **doc_store($doc)** store a document on the CouchDB server. $doc should be an object. If the property $doc->_rev is set, the method understand that it's an update, and as so requires the property $doc->_id to be set. If the property $doc->_rev is not set, the method checks for the existance of property $doc->_id and initiate the appropriate request.

The response of this method is the CouchDB server response. In other words if the request ends successfully the returned object should be :

    stdClass ( "ok" => true, "id" => "some_doc_id" , "rev" => "3-23423423476" )

Example : creating a document without specifying id

    $new_doc = new stdClass();
    $new_doc->title = "Some content";
    try {
        $response = $client->doc_store($new_doc);
    } catch (Exception $e) {
        echo "ERROR: ".$e->getMessage()." (".$e->getCode().")<br>\n";
    }
    echo "Doc recorded. id = ".$response->id." and revision = ".$response->rev."<br>\n";
    // Doc recorded. id = 0162ff06747761f6d868c05b7aa8500f and revision = 1-249007504
Example : creating a document specifying the id

    $new_doc = new stdClass();
    $new_doc->title = "Some content";
    $new_doc->id = "BlogPost6576";
    try {
        $response = $client->doc_store($new_doc);
    } catch (Exception $e) {
        echo "ERROR: ".$e->getMessage()." (".$e->getCode().")<br>\n";
    }
    echo "Doc recorded. id = ".$response->id." and revision = ".$response->rev."<br>\n";
    // Doc recorded. id = BlogPost6576 and revision = 1-249004576

Example : updating an existing document :

    // get the document
    try {
        $doc = $client->doc_get('BlogPost6576');
    } catch (Exception $e) {
        echo "ERROR: ".$e->getMessage()." (".$e->getCode().")<br>\n";
    }

    // make changes
    $doc->title = 'Some smart content';
    $doc->tags = array('twitter','facebook','msn');

    // update the document on CouchDB server
    try {
        $response = $client->doc_store($doc);
    } catch (Exception $e) {
        echo "ERROR: ".$e->getMessage()." (".$e->getCode().")<br>\n";
    }
    echo "Doc recorded. id = ".$response->id." and revision = ".$response->rev."<br>\n";
    // Doc recorded. id = BlogPost6576 and revision = 2-456769086


Deleting a document
===================

The method **doc_delete ( $doc )** permanently removes $doc from the CouchDB server. $doc should be an object containing at least _id and _rev properties.

Example :

    // get the document
    try {
        $doc = $client->doc_get('BlogPost6576');
    } catch (Exception $e) {
        echo "ERROR: ".$e->getMessage()." (".$e->getCode().")<br>\n";
    }
    // permanently remove the document
    try {
        $client->doc_delete($doc);
    } catch (Exception $e) {
        echo "ERROR: ".$e->getMessage()." (".$e->getCode().")<br>\n";
    }


Copying a document
==================

The **doc_copy($id,$new_id)** method provides an handy way to copy a document. $id is the id of the document to copy. $new_id is the id of the new document.

Upon success, this method returns the CouchDB server response, which has the main form than a document storage :

    stdClass ( "ok" => true, "id" => "new_id" , "rev" => "1-23423423476" )

Example :

    try {
        $response = $client->doc_copy('BlogPost6576','CopyOfBlogPost6576');
    } catch (Exception $e) {
        echo "ERROR: ".$e->getMessage()." (".$e->getCode().")<br>\n";
    }

Attaching a file to a document
==============================

There is two methods handling attachments, it depends whether the file to send as attachment is on the harddrive, or if it's contained in a PHP variable. The first one should be more reliable for large attachments.

On-disk files to attachments
----------------------------

The method **attachment_store($doc,$file,$content_type = 'application/octet-stream',$filename = null) ** handles the process of storing an attachment on a CouchDB document.

* **$doc** is a PHP object containing at least the properties _id ans _rev
* **$file** is the complete path to the file on disk
* **$content_type** is the file's [content-type](http://en.wikipedia.org/wiki/MIME)
* **$filename** is the name of the attachment on CouchDB document, if the name is not the name of the file in $file

Example :

    $doc = $client->get('BlogPost5676');
    $ok = $client->attachment_store($doc,'/etc/resolv.conf','text/plain', 'my-resolv.conf');
    print_r($ok);
    // stdClass ( "ok" => true, "id" => "BlogPost5676" , "rev" => "5-2342345476" )

PHP data to attachments
----------------------------

The method **as_attachment_store($doc,$data,$filename,$content_type = 'application/octet-stream')** records as a CouchDB document's attachment the content of a PHP variable.

* **$doc** is a PHP object containing at least the properties _id ans _rev
* **$data** is the data (the content of the attachment)
* **$filename** is the name of the attachment on CouchDB document
* **$content_type** is the file's [content-type](http://en.wikipedia.org/wiki/MIME)

Example :

    $doc = $client->get('BlogPost5676');
    $google_home=file_get_contents('http://www.google.com/');
    $ok = $client->as_attachment_store($doc,$google_home,'text/html', 'GoogleHomepage.html');
    print_r($ok);
    // stdClass ( "ok" => true, "id" => "BlogPost5676" , "rev" => "5-2342345476" )

Delete a document attachment
============================

the method **attachment_delete($doc,$attachment_name )** delete an attachment from a CouchDB document. $doc is an object with, at least, _id and _rev properties, and $attachment_name is the name of the attachment to delete.

Example :

    $doc = $client->get('BlogPost5676');
    $ok = $client->attachment_delete($doc,'GoogleHomepage.html');


