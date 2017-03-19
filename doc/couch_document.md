This section give details on using CouchDocument data mapper

## CouchDocuments to simplify the code

CouchDB embed a simple JSON/REST HTTP API. You can simplify even more your PHP code using couch documents.
Couch Documents take care of revision numbers, and automatically propagate updates on database.

## Table of content

- [Creating a new document](#creating-a-new-document)
- [set($key, $value = null)](#setkey-value--null)
- [set($params)](#setparams)
- [setAutocommit(boolean $autoCommit)](#setautocommitboolean-autocommit)
- [record()](#record)
- [getAutocommit()](#getautocommit)
- [remove($key)](#removekey)
- [getInstance( CouchClient $client, $docId )](#getinstance-couchclient-client-docid-)
- [getUri()](#geturi)
- [getFields()](#getfields)
- [storeAttachment($file, $content_type = 'application/octet-stream', $filename = null)](#storeattachmentfile-content_type--applicationoctet-stream-filename--null)
- [storeAsAttachment($data, $filename, $content_type = 'application/octet-stream')](#storeasattachmentdata-filename-content_type--applicationoctet-stream)
- [deleteAttachment($name)](#deleteattachmentname)
- [getAttachmentUri($name)](#getattachmenturiname)
- [replicateTo($url, $create_target = false)](#replicatetourl-create_target--false)
- [replicateFrom($id, $url, $create_target = false)](#replicatefromid-url-create_target--false)
- [show($id, $name, $additionnal_parameters = array())](#replicatefromid-url-create_target--false)
- [update($id, $name, $additionnal_params = array())](#updateid-name-additionnal_params--array-)

## Creating a new document


To create an empty CouchDocument, simply instanciate the **CouchDocument** class, passing the CouchClient object as the constructor argument.

Example :

```php
$client = new CouchClient('http://localhost:5984/','myDB');
$doc = new CouchDocument($client);
```
If I set a property on $doc, it'll be registered in the database. If the property is not _id, the unique identifier will be automatically created by CouchDB, and available in the CouchDocument object.

Example :

```php
$doc->type="contact";
echo $doc->id();
// 1961f10823408cc9e1cccc145d35d10d
```

However if you specify _id, that one will of course be used.

Example :

```php
$doc = new CouchDocument($client);
$doc->_id = "some_doc";
echo $doc->id();
// some_doc
```

### set($key, $value = null)

As we just saw, just set the property on the $doc object and it'll be recorded in the database. There are 2 ways to do it. You can either use the **set($key, $value)** method or simply use the setter **$obj->key = $value**.

Example :

```php
$doc = new CouchDocument($client);
$doc->_id = "some_doc";
$doc->type = "page";
$doc->title = "Introduction";
```

### set($params)

It's always possible to set several properties in one query using the **set($params)** method

Example using an array :

```php
$doc = new CouchDocument($client);
$doc->set (
    array(
        '_id'   => 'some_doc',
        'type'  => "page",
        'title' => "Introduction"
    )
);
```

Example using an object

```php
$prop = new stdClass();
$prop->_id = "some_doc";
$prop->type = "page";
$prop->title = "Introduction";

$doc = new CouchDocument($client);
$doc->set ( $prop );
```

### setAutocommit(boolean $autoCommit)

If, for some reason, you need to disable the auto-commit feature, use the **setAutocommit()** method. In this case, you'll have to explicitely call the **record()** method to store your changes on the database.

Example :

```php
$doc = new CouchDocument($client);
$doc->setAutocommit(false);
$doc->_id = "some_doc";
$doc->type = "page";
$doc->title = "Introduction";
$doc->record();
```

### record()

When the auto-commit feature is off, you need to apply changes manually. Calling the method **record()** apply the changes.

Example :

```php
$doc = new CouchDocument($client);
$doc->setAutocommit(false);
$doc->_id = "some_doc";
$doc->type = "page";
$doc->title = "Introduction";
$doc->record();
```

### getAutocommit()

To know if the auto-commit feature is activated, use the **getAutocommit()** method : it returns a boolean.


### remove($key)

To unset a property, just use the **unset** PHP function, as you'll do for a PHP object. You can also use the **remove($key)** function which is normally called when you du a **unset**.

Example :

```php
$prop = new stdClass();
$prop->_id = "some_doc";
$prop->type = "page";
$prop->title = "Introduction";

$doc = new CouchDocument($client);
$doc->set ( $prop );
unset($doc->title);
echo $doc->title ; // won't echo anything
```

### getInstance( CouchClient $client, $docId )

The static method **getInstance( CouchClient $client, $docId )** returns a CouchDocument when the specified id exists :

Example :

```php
$doc = CouchDocument::getInstance($client,'some_doc');
echo $doc->_rev."\n";
echo $doc->type;
```

### getUri()

The method **getUri()** sends back a string giving the current document URI.

Example :

```php
echo $doc->getUri();
/*
db.example.com:5984/testdb/dome_doc_id
*/
```

### getFields()

To get the Couch document fields from a CouchDocument object, use the **getFields()** method


Example :

```php
$doc = CouchDocument::getInstance($client,'some_doc');
print_r($doc->getFields());
/*
    stdClass object {
        "_id"  => "some_doc",
        "_rev" => "3-234234255677684536",
        "type" => "page",
        "title"=> "Introduction"
    }
*/
```

### storeAttachment($file, $content_type = 'application/octet-stream', $filename = null)

*When the attachment is a file on-disk*

The method **storeAttachment($file, $content_type = 'application/octet-stream', $filename = null)** adds a new attachment, or update the attachment if it already exists. The attachment contents is located on a file.

Example - Store the file /path/to/some/file.txt as an attachment of document id "some_doc" :

```php
$doc = CouchDocument::getInstance($client,'some_doc');
try {
    $doc->storeAttachment("/path/to/some/file.txt","text/plain");
} catch (Exception $e) {
    echo "Error: attachment storage failed : ".$e->getMessage().' ('.$e->getCode().')';
}
```

### storeAsAttachment($data, $filename, $content_type = 'application/octet-stream')

The method **storeAsAttachment($data, $filename, $content_type = 'application/octet-stream')** adds a new attachment, or update the attachment if it already exists. The attachment contents is contained in a PHP variable.

Example - Store "Hello world !\nAnother Line" as an attachment named "file.txt" on document "some_doc" :

```php
$doc = CouchDocument::getInstance($client,'some_doc');
try {
    $doc->storeAsAttachment("Hello world !\nAnother Line", "file.txt" , "text/plain");
} catch (Exception $e) {
    echo "Error: attachment storage failed : ".$e->getMessage().' ('.$e->getCode().')';
}
```

### deleteAttachment($name)

The method **deleteAttachment($name)** permanently removes an attachment from a document.
    
Example - Deletes the attachment "file.txt" of document "some_doc" :

```php
$doc = CouchDocument::getInstance($client,'some_doc');
try {
    $doc->deleteAttachment("file.txt");
} catch (Exception $e) {
    echo "Error: attachment removal failed : ".$e->getMessage().' ('.$e->getCode().')';
}
```

### getAttachmentUri($name)

The method **getAttachmentUri($name)** returns the URI of an attachment.

Example :

```php
$doc = CouchDocument::getInstance($client,'some_doc');
if ( $doc->_attachments ) {
    foreach ( $doc->_attachments as $name => $infos ) {
        echo $name.' '.$doc->getAttachmentURI($name); 
        // should say something like "file.txt http://localhost:5984/dbname/some_doc/file.txt"
    }
}
try {
    $doc->deleteAttachment("file.txt");
} catch (Exception $e) {
    echo "Error: attachment removal failed : ".$e->getMessage().' ('.$e->getCode().')';
}
```

### replicateTo($url, $create_target = false)

The CouchDocuments instance provides an easy way to replicate a document to, or from, another database. Think about replication like a copy-paste operation of the document to CouchDB databases.

For those methods to work, you should have included the CouchReplicator class file src/CouchReplicator.php .


Use the **replicateTo($url, $create_target = false)** method to replicate a CouchDocument to another CouchDB database. The create_target parameter let you create the remote database if it's not existing.

Example :

```php
$client = new CouchClient("http://couch.server.com:5984/","mydb");
// load an existing document
$doc = CouchDocument::getInstance($client,"some_doc_id");
// replicate document to another database
$doc->replicateTo("http://another.server.com:5984/mydb/");
```
The replicateTo can have another argument, a boolean one. If true, the database will be created on the destination server if it doesn't exist.


### replicateFrom($id, $url, $create_target = false)

Use the **replicateFrom($id, $url, $create_target = false)** method to replicate a CouchDocument from another CouchDB database, and then load it into the CouchDocument instance.

Example :

```php
$client = new CouchClient("http://couch.server.com:5984/","mydb");
// load an existing document
$doc = new CouchDocument($client);

// replicate document from another database, and then load it into $doc
$doc->replicateFrom("some_doc_id","http://another.server.com:5984/mydb/");
echo $doc->_id ; (should return "some_doc_id")
$doc->type="foo"; // doc is recorded on "http://couch.server.com:5984/mydb"

// then replicate $doc back to http://another.server.com:5984/mydb/
$doc->replicateTo("http://another.server.com:5984/mydb/");
```

The replicateFrom can have another argument, a boolean one. If true, the database will be created on the destination server if it doesn't exist.

### show($id, $name, $additionnal_parameters = array())

The **show($id,$name,$additionnal_parameters)** method parses the current document through a CouchDB show function.

Example : the database contains the following design document :

```php
{
    "_id": "_design/clean",
    "shows": {
        "html": "function (doc, req) {
                    send('<p>ID: '+doc._id+', rev: '+doc._rev+'</p>');
                }"
    }
}
```

and another document that got the id "some_doc". We load the "some_doc" document as a CouchDocument object:

```php
$doc = CouchDocument::getInstance($client,"some_doc");
```

We can then request CouchDB to parse this document through a show function :

```php
$html = $doc->show("clean","html");
// html should contain "<p>ID: some_doc, rev: 3-2342342346</p>"
```

The show method is a proxy method to the **getShow()** method of **CouchClient**.

### update($id, $name, $additionnal_params = array())

The **update($id,$name,$additionnal_params)** method allows to use the CouchDB [update handlers](http://wiki.apache.org/couchdb/Document_Update_Handlers) feature to update an existing document.
The CouchDocument object shouldd have an id for this to work ! Please see **CouchClient** *updateDoc* method for more infos.
