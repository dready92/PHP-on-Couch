# Couch

## Table of content

- [**Summary**](#summary)
- [**General functions**](#general-functions)
    + [**dsn()**](#dsn)
    + [**options()**](#options)
    + [**getSessionCookie()**](#getsessioncookie)
    + [**setSessionCookie($cookie)**](#setsessioncookiecookie)
    + [**query($method, $url, $parameters = [], $data = null, $contentType = null)**](#querymethod-url-parameters---data--null-contenttype--null)
    + [**continuousQuery($callable, $method, $url, $parameters = [], $data = null)**](#continuousquerycallable-method-url-parameters---data--null)
    + [**storeFile($url, $file, $contentType)**](#storefileurl-file-contenttype)
    + [**storeAsFile($url, $data, $contentType)**](#storeasfileurl-data-contenttype)
    
- [**Adapters**](#adapters)
    + [**initAdapter($options())**](#initadapteroptions)
    + [**getAdapter()**](#getadapter)
    + [**setAdapter(CouchHttpAdapterInterface $adapter)**](#setadaptercouchhttpadapterinterface-adapter)

## Summary

The Couch.php class is the one of the low level class that is used to handle the communication between the high level classes and CouchDB. Before version **2.0.2**, the default Http adapter was curl and all the possible adapters where declared into the Couch.php class. With **2.0.2**, the library code has been refactored so  that the Http adapters are declared into separate classes. The Couch class nowaday use a HttpAdapterInterface to communicate with CouchDB.

**Note**: The following methods are public methods of the Couch class. Therefore, you will mostly use the high level classes which usually inherit the Couch class. For example, all the following methods will be directly available from the CouchClient class.

## General functions 

### dsn()

Getter the return the dsn set to the current Couch class.

### options()

Getter the return the options passed to the Couch class.

### getSessionCookie()

Return the current session cookie.

### setSessionCookie($cookie)

Set the current session cookie.

### query($method, $url, $parameters = [], $data = null, $contentType = null)

Send a query to the CouchDB server. 

**Parameters**

| Name | Type | Default | Desc |
|---|---|---|---|
| $method | string |   | The HTTP method (GET,PUT,POST) |
| $url | string |   | Tue URL to fetch |
| $parameters | array | [] | The query parameters to passe to the query |
| $data | mixed | null | The request body | 
| $contentType | stirng |   | The content type of the data |

**Returns**  the server response or false if an error occured.

### continuousQuery($callable, $method, $url, $parameters = [], $data = null)

Send a query to CouchDB. For each line returned by the server, the $callable will be called. If the callable returns false, the **continuousQuery** will stop.

**Parameters**

| Name | Type | Default | Desc |
|---|---|---|---|
| $callable | function |   | The function called for every document returned |
| $method | string |   | The HTTP method (GET,PUT,POST) |
| $url | string |   | Tue URL to fetch |
| $parameters | array | [] | The query parameters to passe to the query |
| $data | mixed | null | The request body | 

**Returns**  the server response or false if an error occured.

### storeFile($url, $file, $contentType)

Make a request with the $file content passed into the request body. The $file must be on the disk.

**Parameters**

| Name | Type | Default | Desc |
|---|---|---|---|
| $callable | function |   | The function called for every document returned |
| $method | string |   | The HTTP method (GET,PUT,POST) |
| $url | string |   | Tue URL to fetch |
| $parameters | array | [] | The query parameters to passe to the query |
| $data | mixed | null | The request body | 

**Returns**  the server response or false if an error occured.

### storeAsFile($url, $data, $contentType)

Make a request with the $data passed into the request body.

**Parameters**

| Name | Type | Default | Desc |
|---|---|---|---|
| $callable | function |   | The function called for every document returned |
| $method | string |   | The HTTP method (GET,PUT,POST) |
| $url | string |   | Tue URL to fetch |
| $parameters | array | [] | The query parameters to passe to the query |
| $data | mixed | null | The request body | 

**Returns**  the server response or false if an error occured.

## Adapters



### initAdapter($options)

This function is called to initialized the adapter. By default, it will load the cURL adapter. The options passed are the same options passed to the Couch class. It's must be an array of options. **You don't have to call this method.** It will be automatically call when using the Couch class.

Example :

```php

$couch = new Couch("http://localhost:5984");
$couch->initAdapter([]) //Set the curl by default
```


### getAdapter()

This function return the current adapter. If it's not set, the [initAdapter](#initadapter-options) will be called. 

```php
$couch = new Couch("http://localhost:5984");
$adapter = $couch->getAdapter();
$doc =  $adapte->query('GET','db/_all_docs');
```

### setAdapter(CouchHttpAdapterInterface $adapter)

This function set the current adapter of the Couch class. You must specify a class that implements the CouchHttpAdapterInterface.

You can implemented the following adapters :

 - CouchHttpAdapterSocket
 - CouchHttpAdapterCurl (default)

*Note*: Even if the CouchHttpAdapter used is Curl, the Socket adapter is still used for the continuous_query function since it is not implemented with cURL.

```php
use PHPOnCouch\Adapter\CouchHttpAdapterCurl;

$couch = new Couch("http://localhost:5984");
$adapter = new CouchHttpAdapterSocket([]);
$couch->setAdapter($adapter);
```
