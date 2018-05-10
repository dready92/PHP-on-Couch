Views
*****

This section describes how to use PHP on Couch to retrieve views results from a CouchDB server.

Creating views
==============

As said in the `documentation <http://docs.couchdb.org/en/stable/api/ddoc/index.html/>`_ , views are stored in CouchDB documents called *design documents*. So to create a view, you have to create a design document.

Example

.. code-block:: php

    $view_fn="function(doc) { emit(doc.timestamp,null); }";
    $design_doc = new stdClass();
    $design_doc->_id = '_design/all';
    $design_doc->language = 'javascript';
    $design_doc->views = array ( 'by_date'=> array ('map' => $view_fn ) );
    $client->storeDoc($design_doc);

.. php:namespace:: PHPOnCouch

.. php:class:: CouchClient

:hidden:`getView`
"""""""""""""""""

    .. php:method:: getView($id, $name)

        The method **getView($id, $name)** sends back the CouchDB response of a view.

        :params string $id: is the design document id without '_design/'
        :params string $name: is the view name
        :returns: The view response object.

        Example :

        .. code-block:: php

            $result = $client->getView('all','by_date');

View response
=============

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

The CoucClient implements chainable methods to add query parameters. The method names are mapped on their CouchDB counterparts :

* key
* keys
* startkey
* startkey_docid
* endkey
* endkey_docid
* limit
* stale (Deprecated from 2.1.1 and will be removed in 3.0)
* descending
* skip
* group
* group_level
* reduce
* include_docs
* inclusive_end
* attachments
* stable (New from 2.1.1)
* update (New from 2.1.1)

Example querying a view with a startkey, a limit and include_docs

.. code-block:: php

    $response = $client->startkey(100000000)->limit(100)->include_docs(true)->getView('all','by_date');

Which is the same as :

.. code-block:: php

    $client->startkey(100000000);
    $client->limit(100);
    $client->include_docs(true);
    $response = $client->getView('all','by_date');

:hidden:`setQueryParameters`
""""""""""""""""""""""""""""

    .. php:method:: setQueryParameters($params)

        You also can set query parameters with a PHP array, using the **setQueryParameters** method :

        :params array $params: A associative array of parameters to set.

        Example:

        .. code-block:: php

            $opts = array ( "include_docs" => true, "limit" => 10, "descending" => true );
            $response = $client->setQueryParameters(opts)->getView("all","by_date");

:hidden:`asArray`
"""""""""""""""""

    .. php:method:: asArray()

        When converting a JSON object to PHP, we can choose the type of the value returned from a CouchClient query.

        Take for example the following JSON object :

        .. code-block:: json

            { "blog" : true, "comments" : { "title" : "cool" } }

        This can be converted into a PHP object :

        .. code-block:: php

            stdClass Object
            (
                [blog] => true
                [comments] => stdClass Object
                    (
                        [title] => "cool"
                    )
            )

        OR into a PHP array :

        .. code-block:: php

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

        .. code-block:: php

            $response = $client->startkey(100000000)->limit(100)->include_docs(true)->asArray()->getView('all','by_date');

Format a view with CouchDB list formatting feature
==================================================

More infos on `CouchDB lists <http://wiki.apache.org/couchdb/Formatting_with_Show_and_List)/>`_ .

:hidden:`getList`
"""""""""""""""""

    .. php:method:: getList($design_id, $name, $view_name, $additionnal_parameters = array())

        This method retrieve a view and then format it using the algorithm of the $name list.

        :params string $design_id: The id of the design document(without the _design part)
        :params string $name: The name of the formatting algorithm.
        :params string $view_name: The name of the view to use.
        :params array $additionnal_parameters: The additionnal parameters.

        Example :

        .. code-block:: php

            $response = $client->limit(100)->include_docs(true)->getList('all','html','by_date');
            // will run the view declared in _design/all and named *by_date*, and then
            // pass it through the list declared in _design/all and named *html*.

:hidden:`getForeignList`
""""""""""""""""""""""""

    .. php:method:: getForeignList($list_design_id, $name, $view_design_id, $view_name, $additionnal_parameters = array())

        Retrieve a view defined in the document *_design/$view_design_id* and then format it using the algorithm of the list defined in the design document *_design/$list_design_id*.

        :params string $list_design_id: The list design id
        :params string $view_design_id: The view design id
        :params array $additionnal_parameters: The additionnal parameters that can be passed.

        Example :

        .. code-block:: php

            $response = $client->limit(100)->getForeignList('display','html','posts','by_date');
            // will run the view declared in _design/posts and named *by_date*, and then
            // pass it through the list declared in _design/display and named *html*.


:hidden:`getViewInfos`
""""""""""""""""""""""

    .. php:method:: getViewInfos($design_id)

        More info on view informations `here <http://docs.couchdb.org/en/stable/api/ddoc/common.html#db-design-design-doc-info/>`_

        The method **getViewInfos($design_id)** sends back some useful informations about a particular design document.

        :params string $design_id: The id of the design document to use
        :returns stdClass:
            Returns an object with the following properties:

            - name: The design document name
            - view_index: `View index informations <http://docs.couchdb.org/en/stable/api/ddoc/common.html#view-index-information/>`_



        Example :

        .. code-block:: php

            $response = $client->getViewInfos("mydesigndoc");
