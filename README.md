[![Latest Stable Version](https://poser.pugx.org/php-on-couch/php-on-couch/version)](https://packagist.org/packages/php-on-couch/php-on-couch)[![Latest Unstable Version](https://poser.pugx.org/php-on-couch/php-on-couch/v/unstable)](//packagist.org/packages/php-on-couch/php-on-couch)[![Build Status](https://travis-ci.org/PHP-on-Couch/PHP-on-Couch.svg?branch=master)](https://travis-ci.org/PHP-on-Couch/PHP-on-Couch)[![Documentation Status](https://readthedocs.org/projects/php-on-couch/badge/?version=latest)](http://php-on-couch.readthedocs.io/en/latest/?badge=latest)[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/PHP-on-Couch/PHP-on-Couch/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/PHP-on-Couch/PHP-on-Couch/?branch=master)[![codecov](https://codecov.io/gh/PHP-on-Couch/PHP-on-Couch/branch/master/graph/badge.svg)](https://codecov.io/gh/PHP-on-Couch/PHP-on-Couch)[![License](https://poser.pugx.org/php-on-couch/php-on-couch/license)](https://packagist.org/packages/php-on-couch/php-on-couch)

:fire:For the complete documentation, visit http://php-on-couch.readthedocs.io :fire:



## Table of content
- [Introduction](#introduction)
- [What's new](#whats-new)
- [Changes](#changes)
- [Installation and testing](#installation-and-testing)
- [Components and documentation](#components-and-documentation)
- [Quick-start guide](#quick-start-guide)
- [Example](#example)
- [Community](#community)
    + [Contributions](#contributions)
    + [Feedback](#feedback)

## Introduction

[PHP On Couch](http://github.com/PHP-on-Couch/PHP-on-Couch/) provides an easy way to work with your [CouchDB](http://couchdb.apache.org) [documents](http://docs.couchdb.org/) with [PHP](http://php.net). 

Supports PHP 5.6 and higher 🚀 

## Recent changes

For the complete change list, head over [here](http://php-on-couch.readthedocs.io/en/latest/overview/changelist/index.html)

## Installation and testing

Install the library using composer : `composer require php-on-couch/php-on-couch`.
You can find more detailed informations about installation [here](http://php-on-couch.readthedocs.io/en/latest/quickstart/installation.html).

To test the the application, see [this topic](http://php-on-couch.readthedocs.io/en/latest/quickstart/testing.html).

## Components and documentation

For the full API document, please visite [this link](http://php-on-couch.readthedocs.io/en/latest/api/index.html)

## Example

For full examples, refer to the [database example](examples/01_databases.php) or the [document example](examples/02_documents_basics.php).



At first, you need to import the main components through their namespace. If you use composer, I suggest you to use their autoload wich is easy to setup. Otherwise, you can use your own autoload function or a basic require with some namespace escaping.

```php
use  PHPOnCouch\CouchClient; //The CouchDB client object

```

Here's an example for basic operations

```php
// Set a new connector to the CouchDB server
$client = new CouchClient('http://my.couch.server.com:5984', 'my_database');

// document fetching by ID
$doc = $client->getDoc('some_doc_id');
// updating document
$doc->newproperty = array("hello !", "world");
try {
    $client->storeDoc($doc);
} catch (Exception $e) {
    echo "Document storage failed : " . $e->getMessage() . "<BR>\n";
}
```

Here's a quick example of how to fetch a view

```php
// view fetching, using the view option limit
try {
    $view = $client->limit(100)->getView('orders', 'by-date');
} catch (Exception $e) {
    echo "something weird happened: " . $e->getMessage() . "<BR>\n";
}
```

Finally, how to use the CouchDocument class.

```php
//using couch_document class :
$doc = new CouchDocument($client);
$doc->set(array('_id' => 'JohnSmith', 'name' => 'Smith', 'firstname' => 'John')); //create a document and store it in the database
echo $doc->name; // should echo "Smith"
$doc->name = "Brown"; // set document property "name" to "Brown" and store the updated document in the database
```

       
## Community

### Contributions

Feel free to make any contributions. All contributions must follow the [code style](http://php-on-couch.readthedocs.io/en/latest/quickstart/codestyle.html) and must also comes with valid and complete tests. 

Help is really appreciated to complete add more tests.

### Feedback

[![Gitter chat](https://badges.gitter.im/gitterHQ/gitter.png)](https://gitter.im/PHP-on-Couch/PHP-on-Couch)

Don't hesitate to submit feedback, bugs and feature requests ! Our contact address is [phponcouch@gmail.com](mailto:phponcouch@gmail.com?subject=Feedback)


