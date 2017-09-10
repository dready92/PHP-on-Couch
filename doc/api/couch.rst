
.. toctree::
    :numbered:
    :maxdepth: 3

Couch class
***********


Summary
#######

The Couch.php class is the one of the low level class that is used to handle the communication between the high level classes and CouchDB. Before version **2.0.2**, the default Http adapter was curl and all the possible adapters where declared into the Couch.php class. With **2.0.2**, the library code has been refactored so  that the Http adapters are declared into separate classes. The Couch class nowaday use a HttpAdapterInterface to communicate with CouchDB.

**Note**: The following methods are public methods of the Couch class. Therefore, you will mostly use the high level classes which usually inherit the Couch class. For example, all the following methods will be directly available from the CouchClient class.

API Reference
#############

.. php:namespace:: PHPOnCouch

.. php:class:: Couch

    This is the low level class that handles communications with CouchDB.

:hidden:`dsn`
"""""""""""""

    .. php:method:: dsn()

        :returns: The dsn of the current Couch instance

:hidden:`options`
"""""""""""""""""

    .. php:method:: options()

        :returns:  The options passed to the Couch instance.

:hidden:`getSessionCookie`
""""""""""""""""""""""""""

    .. php:method:: getSessionCookie()

        :returns: The current session cookie. Returns null if not set.

:hidden:`setSessionCookie`
""""""""""""""""""""""""""

    .. php:method:: setSessionCookie($cookie)

        Set the current session cookie.

        :params string $cookie: The cookie to set

:hidden:`query`
"""""""""""""""

    .. php:method:: query($method, $url, $parameters = array(), $data = null, $contentType = null)

        Send a query to the CouchDB server.

        :params string $method: The HTTP method to use (GET,PUT,POST,...)
        :params string $url: The URL to fetch
        :params array $parameters: The query parameters to pass to the query
        :params mixed $data: The request body(null by default)
        :params string $contentType: The content type of the data.
        :returns: The server response or false if an error occured.

:hidden:`continuousQuery`
"""""""""""""""""""""""""

    .. php:method:: continuousQuery($callable, $method, $url, $parameters = array(), $data = null)

        Send a query to CouchDB. For each line returned by the server, the $callable will be called. If the callable returns false, the **continuousQuery** will stop.

        :params Function $callable: The function called for every document returned.
        :params string $method: The HTTP method to use (GET,PUT,POST,...)
        :params string $url: The URL to fetch
        :params array $parameters: The query parameters to pass to the query
        :params mixed $data: The request body(null by default)
        :params string $contentType: The content type of the data.
        :returns: The server response or false if an error occured.

:hidden:`storeFile`
"""""""""""""""""""

    .. php:method:: storeFile($url, $file, $contentType)

        Make a request with the $file content passed into the request body. The $file must be on the disk.

        :params function $callable: The function called for every document returned
        :params string $method: The HTTP method to use (GET,PUT,POST,...)
        :params string $url: The URL to fetch
        :params array $parameters: The query parameters to pass to the query
        :params mixed $data: The request body(null by default)
        :params string $contentType: The content type of the data.
        :returns: The server response or false if an error occured.

:hidden:`storeAsFile`
"""""""""""""""""""""

    .. php:method:: storeAsFile($url, $data, $contentType)

        Make a request with the $data passed into the request body.

        :params function $callable: The function called for every document returned
        :params string $method: The HTTP method to use (GET,PUT,POST,...)
        :params string $url: The URL to fetch
        :params array $parameters: The query parameters to pass to the query
        :params mixed $data: The request body(null by default)
        :params string $contentType: The content type of the data.
        :returns: The server response or false if an error occured.

:hidden:`initAdapter`
"""""""""""""""""""""

    .. php:method:: initAdapter($options)

        This function is called to initialized the adapter. By default, it will load the cURL adapter. The options passed are the same options passed to the Couch class. It's must be an array of options. **You don't have to call this method.** It will be automatically call when using the Couch class.

        :params array $options: The options passed to the Couch instance

        Example :

        .. code-block:: php

            $couch = new Couch("http://localhost:5984");
            $couch->initAdapter([]) //Set the curl by default


:hidden:`getAdapter`
""""""""""""""""""""

    .. php:method:: getAdapter()

        This function return the current adapter. If it's not set, the :meth:`Couch::initAdapter` will be called.

        :returns: The Adapter currently used.

        Example :

        .. code-block:: php

            $couch = new PHPOnCouch\Couch("http://localhost:5984");
            $adapter = $couch->getAdapter();
            $doc =  $adapte->query('GET','db/_all_docs');

:hidden:`setAdapter`
""""""""""""""""""""

    .. php:method:: setAdapter(CouchHttpAdapterInterface $adapter)

        This function set the current adapter of the Couch class. You must specify a class that implements the CouchHttpAdapterInterface.

        :params CouchHttpAdapterInterface $adapter: The adapter to set.

        You can implemented the following adapters :

         - CouchHttpAdapterSocket
         - CouchHttpAdapterCurl (default)

        .. note ::

            Even if the CouchHttpAdapter used is Curl, the Socket adapter is still used for the continuous_query function since it is not implemented with cURL.

        Example:

        .. code-block:: php

            use PHPOnCouch\Adapter\CouchHttpAdapterCurl;

            $couch = new PHPOnCouch\Couch("http://localhost:5984");
            $adapter = new CouchHttpAdapterSocket([]);
            $couch->setAdapter($adapter);
