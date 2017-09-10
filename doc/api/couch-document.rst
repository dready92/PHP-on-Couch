
.. toctree::
    :numbered:
    :maxdepth: 3
    :caption: CouchDocument

CouchDocument class
*******************

This section give details on using CouchDocument data mapper.

CouchDocuments to simplify the code
===================================

CouchDB embed a simple JSON/REST HTTP API. You can simplify even more your PHP code using couch documents.
Couch Documents take care of revision numbers, and automatically propagate updates on database.


Creating a new document
=======================


To create an empty CouchDocument, simply instanciate the **CouchDocument** class, passing the CouchClient object as the constructor argument.

Example :

.. code-block:: php

    $client = new CouchClient('http://localhost:5984/','myDB');
    $doc = new CouchDocument($client);

If I set a property on $doc, it'll be registered in the database. If the property is not _id, the unique identifier will be automatically created by CouchDB, and available in the CouchDocument object.

Example :

.. code-block:: php

    $doc->type="contact";
    echo $doc->id();
    // 1961f10823408cc9e1cccc145d35d10d

However if you specify _id, that one will of course be used.

Example :

.. code-block:: php

    $doc = new CouchDocument($client);
    $doc->_id = "some_doc";
    echo $doc->id();
    // some_doc

API Reference
=============

.. php:namespace:: PHPOnCouch

.. php:class:: CouchDocument

    A CouchDocument is a class that maps a document object.


:hidden:`set($key,$value)`
""""""""""""""""""""""""""

    .. php:method:: set($key, $value = null)

        As we just saw, just set the property on the $doc object and it'll be recorded in the database. There are 2 ways to do it.
        You can either use the **set($key, $value)** method or simply use the setter **$obj->key = $value**.

        :params string $key: The key to set
        :params string $value: The value to set to the key.

        Example :

        .. code-block:: php

            $doc = new CouchDocument($client);
            $doc->_id = "some_doc";
            $doc->type = "page";
            $doc->title = "Introduction";

:hidden:`set(array $params)`
""""""""""""""""""""""""""""

        .. php:method:: set(array $params)

            It's always possible to set several properties in one query using the **set($params)** method

            :params array $params: An associative array of parameters that will be set.

            Example using an array :

            .. code-block:: php

                $doc = new CouchDocument($client);
                $doc->set (
                    array(
                        '_id'   => 'some_doc',
                        'type'  => "page",
                        'title' => "Introduction"
                    )
                );

            Example using an object

            .. code-block:: php

                $prop = new stdClass();
                $prop->_id = "some_doc";
                $prop->type = "page";
                $prop->title = "Introduction";

                $doc = new CouchDocument($client);
                $doc->set ( $prop );

:hidden:`setAutocommit`
"""""""""""""""""""""""

        .. php:method:: setAutocommit(boolean $autoCommit)

            If, for some reason, you need to disable the auto-commit feature, use the **setAutocommit()** method.
            In this case, you'll have to explicitely call the **record()** method to store your changes on the database.

            :params boolean $autoCommit: Determine if the autocommit option should be enabled or not.

            Example :

            .. code-block:: php

                $doc = new CouchDocument($client);
                $doc->setAutocommit(false);
                $doc->_id = "some_doc";
                $doc->type = "page";
                $doc->title = "Introduction";
                $doc->record();

:hidden:`record`
""""""""""""""""

        .. php:method:: record()

            When the auto-commit feature is off, you need to apply changes manually. Calling the method **record()** apply the changes.

            Example :

            .. code-block:: php

                $doc = new CouchDocument($client);
                $doc->setAutocommit(false);
                $doc->_id = "some_doc";
                $doc->type = "page";
                $doc->title = "Introduction";
                $doc->record();

:hidden:`getAutocommit`
"""""""""""""""""""""""

        .. php:method:: getAutocommit()

           :returns: True if autocommit is enabled. Otherwise false.


:hidden:`remove`
""""""""""""""""

        .. php:method:: remove($key)

            To unset a property, just use the **unset** PHP function, as you'll do for a PHP object.
            You can also use the **remove($key)** function which is normally called when you du a **unset**.

            :params string $key: The key of property to unset.

            Example :

            .. code-block:: php

                $prop = new stdClass();
                $prop->_id = "some_doc";
                $prop->type = "page";
                $prop->title = "Introduction";

                $doc = new CouchDocument($client);
                $doc->set ( $prop );
                unset($doc->title);
                echo $doc->title ; // won't echo anything


:hidden:`getInstance`
"""""""""""""""""""""

        .. php:method:: getInstance( CouchClient $client, $docId )

            The static method **getInstance( CouchClient $client, $docId )** returns a CouchDocument when the specified id exists :

            :params CouchClient $client: The CouchClient instance initialized.
            :params string $docId: The _id of the document to use.

            Example :

            .. code-block:: php

                $doc = CouchDocument::getInstance($client,'some_doc');
                echo $doc->_rev."\n";
                echo $doc->type;

:hidden:`getUri`
""""""""""""""""

        .. php:method:: getUri()

            The method **getUri()** sends back a string giving the current document URI.

            :returns: The document URI.

            Example :

            .. code-block:: php

                echo $doc->getUri();
                /*
                db.example.com:5984/testdb/dome_doc_id
                */

:hidden:`getFields`
"""""""""""""""""""

        .. php:method:: getFields()

            To get the Couch document fields from a CouchDocument object, use the **getFields()** method

            :returns: Returns an object with the fields of the document.

            Example :

            .. code-block:: php

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

:hidden:`storeAttachment`
"""""""""""""""""""""""""

        .. php:method:: storeAttachment($file, $content_type = 'application/octet-stream', $filename = null)

            .. note:: When the attachment is a file on-disk

            Adds a new attachment or update the attachment if it already exists. The attachment contents is located on a file.

            :params string $file: The absolute path of the file.
            :params string $content_type: The Content-Type of the file.
            :params string $filename: The desired name of the stored attachment.

            Example - Store the file /path/to/some/file.txt as an attachment of document id "some_doc" :

            .. code-block:: php

                $doc = CouchDocument::getInstance($client,'some_doc');
                try {
                    $doc->storeAttachment("/path/to/some/file.txt","text/plain");
                } catch (Exception $e) {
                    echo "Error: attachment storage failed : ".$e->getMessage().' ('.$e->getCode().')';
                }

:hidden:`storeAsAttachment`
"""""""""""""""""""""""""""

        .. php:method:: storeAsAttachment($data, $filename, $content_type = 'application/octet-stream')

            Adds a new attachment, or update the attachment if it already exists. The attachment contents is contained in a PHP variable.

            :params string $data: The data to store as an attachment.
            :params string $filename: The desired name of the stored attachment.
            :params string $content_type: The Content-Type of the file.

            Example - Store "Hello world !\nAnother Line" as an attachment named "file.txt" on document "some_doc" :

            .. code-block:: php

                $doc = CouchDocument::getInstance($client,'some_doc');
                try {
                    $doc->storeAsAttachment("Hello world !\nAnother Line", "file.txt" , "text/plain");
                } catch (Exception $e) {
                    echo "Error: attachment storage failed : ".$e->getMessage().' ('.$e->getCode().')';
                }

:hidden:`deleteAttachment`
""""""""""""""""""""""""""

        .. php:method:: deleteAttachment($name)

            Permanently removes an attachment from a document.

            :params string $name: The name of the attachment to delete.

            Example - Deletes the attachment "file.txt" of document "some_doc" :

            .. code-block:: php

                $doc = CouchDocument::getInstance($client,'some_doc');
                try {
                    $doc->deleteAttachment("file.txt");
                } catch (Exception $e) {
                    echo "Error: attachment removal failed : ".$e->getMessage().' ('.$e->getCode().')';
                }

:hidden:`getAttachmentUri`
""""""""""""""""""""""""""

        .. php:method:: getAttachmentUri($name)

            :params string $name: The name of the attachment to get the URI.

            :returns: Returns the URI of an attachment.

            Example :

            .. code-block:: php

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

:hidden:`replicateTo`
"""""""""""""""""""""

        .. php:method:: replicateTo($url, $create_target = false)

            Replicate a CouchDocument to another CouchDB database. The create_target parameter let you create the remote database if it's not existing.
            The CouchDocuments instance provides an easy way to replicate a document to, or from, another database.
            Think about replication like a copy-paste operation of the document to CouchDB databases.

            For those methods to work, you should have included the CouchReplicator class file src/CouchReplicator.php .

            :params string $url: The url of the remote database to replicate to.
            :params boolean $create_target: If true, create the target database if it doesn't exists.

            Example :

            .. code-block:: php

                $client = new CouchClient("http://couch.server.com:5984/","mydb");
                // load an existing document
                $doc = CouchDocument::getInstance($client,"some_doc_id");
                // replicate document to another database
                $doc->replicateTo("http://another.server.com:5984/mydb/");


:hidden:`replicateFrom`
"""""""""""""""""""""""

        .. php:method:: replicateFrom($id, $url, $create_target = false)

            Replicate a CouchDocument from another CouchDB database, and then load it into the CouchDocument instance.

            :params string $id: Replicate from this target document id.
            :params string $url: The url of the remote database to replicate to.
            :params boolean $create_target: If true, create the target database if it doesn't exists.

            Example :

            .. code-block:: php

                $client = new CouchClient("http://couch.server.com:5984/","mydb");
                // load an existing document
                $doc = new CouchDocument($client);

                // replicate document from another database, and then load it into $doc
                $doc->replicateFrom("some_doc_id","http://another.server.com:5984/mydb/");
                echo $doc->_id ; (should return "some_doc_id")
                $doc->type="foo"; // doc is recorded on "http://couch.server.com:5984/mydb"

                // then replicate $doc back to http://another.server.com:5984/mydb/
                $doc->replicateTo("http://another.server.com:5984/mydb/");


:hidden:`show`
""""""""""""""

        .. php:method:: show($id, $name, $additionnal_parameters = array())

            Parses the current document through a CouchDB show function.

            .. note:: The show method is a proxy method to the **getShow()** method of **CouchClient**.

            :params string $id: The name of the _design document.
            :params string $name: The name of the show function
            :params array $additionnal_parameters: Additional parameters

            Example : the database contains the following design document :

            .. code-block:: json

                {
                    "_id": "_design/clean",
                    "shows": {
                        "html": "function (doc, req) {
                                    send('<p>ID: '+doc._id+', rev: '+doc._rev+'</p>');
                                }"
                    }
                }

            and another document that got the id "some_doc". We load the "some_doc" document as a CouchDocument object:

            .. code-block:: php

                $doc = CouchDocument::getInstance($client,"some_doc");

            We can then request CouchDB to parse this document through a show function :

            .. code-block:: php

                $html = $doc->show("clean","html");
                // html should contain "<p>ID: some_doc, rev: 3-2342342346</p>"

:hidden:`update`
""""""""""""""""

        .. php:method:: update($id, $name, $additionnal_params = array())

            Allows to use the CouchDB `update handlers <http://wiki.apache.org/couchdb/Document_Update_Handlers/>`_ feature to update an existing document.
            The CouchDocument object shouldd have an id for this to work ! Please see :meth:`CouchClient::updateDoc` method for more infos.
