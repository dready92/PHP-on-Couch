Configuration
=============

Since version 2.0.4, a configuration systeme has been implemented.

Configuration is made via environments variables. You can either:

- Define environments variables on your system
- Define configuration in a .env file directly at the root of the PHPOnCouch.

Configuration via file
----------------------

In the *src* folder, you should see a file named: *.env.example*.

Copy the *.env.example* file to *.env*

Now, you can open the *.env* file and start configuration your library!

Configurations available
------------------------

At the moment, you can configure curl options and the adapter used internally.

The HTTP adapter can either be curl or socket. By default, it will be curl if available.

    .. code-block::

        HTTP_ADAPTER=curl


If you are using a cURL adapter, you can specify CURL_OPT directly in the configuration file.
Here's an example:

    .. code-block:: env

        CURLOPT_SSL_VERIFYPEER=1
