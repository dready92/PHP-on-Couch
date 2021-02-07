<?php

/*
  Copyright (C) 2017  Alexis Cote

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Lesser General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Lesser General Public License for more details.

  You should have received a copy of the GNU Lesser General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace PHPOnCouch\Adapter;

define('PHPONCOUCH_AUTH_NONE', 1);
define('PHPONCOUCH_AUTH_BASIC', 2);
define('PHPONCOUCH_AUTH_COOKIE', 3);

/**
 * Description of CouchHttpAdapterBase
 *
 * @author alexis
 */
abstract class AbstractCouchHttpAdapter implements CouchHttpAdapterInterface
{

    protected $dsn = null;

    /**
     * @var array database source name parsed
     */
    protected $dsnParsed = null;
    protected $options = null;

    /**
     *
     * @var array allowed HTTP methods for REST dialog
     */
    protected $httpMethods = [
        self::METHOD_PUT,
        self::METHOD_POST,
        self::METHOD_GET,
        self::METHOD_DELETE,
        self::METHOD_COPY,
    ];

    protected $authToken = null;
    protected $authMode = PHPONCOUCH_AUTH_NONE;

    public function setDsn($dsn)
    {
        $this->dsn = preg_replace('@/+$@', '', $dsn);
        if (($parsed = parse_url($this->dsn))) {
            $this->dsnParsed = $parsed;
        }

        if (!isset($this->dsnParsed['port'])) {
            $this->dsnParsed['port'] = 80;
        }
    }

    public function getDsn()
    {
        return $this->dsn;
    }

    public function setOptions($options)
    {
        $this->options = $options;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function __construct($dsn, $options = [])
    {
        $this->setOptions($options);
        $this->setDsn($dsn);

        if (array_key_exists('username', $options)) {
            $username = $options['username'];
            $password = array_key_exists('password', $options) ? $options['password'] : '';
            $this->authToken = $username.':'.$password;
            $this->authMode = PHPONCOUCH_AUTH_BASIC;
        }
    }

    /**
     * set the session cookie to send in the headers
     *
     * @param string $cookie
     *            the session cookie ( example : AuthSession=Y291Y2g6NENGNDgzNz )
     *
     * @return \PHPOnCouch\Adapter\AbstractCouchHttpAdapter
     */
    public function setSessionCookie($cookie)
    {
        $this->authToken = $cookie;
        $this->authMode = PHPONCOUCH_AUTH_COOKIE;

        return $this;
    }

    /**
     * get the session cookie
     *
     * @return string cookie
     */
    public function getSessionCookie()
    {
        if ($this->authMode == PHPONCOUCH_AUTH_COOKIE) {
            return $this->authToken;
        } else {
            throw new \Exception(
                "The CouchClient is not using a cookie authentication. Current mode: ".$this->authMode
            );
        }
    }

    /**
     * get the session cookie
     *
     * @return string cookie
     */
    public function hasSessionCookie()
    {
        return $this->authMode == PHPONCOUCH_AUTH_COOKIE && (bool)$this->authToken;
    }

    public function getAuthMode()
    {
        return $this->authMode;
    }

    public function getAuthToken()
    {
        return $this->authToken;
    }

    /**
     * return a part of the data source name
     *
     * if $part parameter is empty, returns dns array
     *
     * @param string $part part to return
     * @return string DSN part
     */
    protected function dsnPart($part = '')
    {
        if (strlen($part) <= 0) {
            return $this->getDsn();
        }
        if (isset($this->dsnParsed[$part])) {
            return $this->dsnParsed[$part];
        }
    }

    abstract public function query($method, $url, $parameters = [], $data = null, $contentType = null);

    abstract public function storeAsFile($url, $data, $contentType);

    abstract public function storeFile($url, $file, $contentType);

    abstract public function continuousQuery($callable, $method, $url, $parameters = [], $data = null, $caller = null);
}
