<?php
/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2013 César Rodas                                                  |
  +---------------------------------------------------------------------------------+
  | Redistribution and use in source and binary forms, with or without              |
  | modification, are permitted provided that the following conditions are met:     |
  | 1. Redistributions of source code must retain the above copyright               |
  |    notice, this list of conditions and the following disclaimer.                |
  |                                                                                 |
  | 2. Redistributions in binary form must reproduce the above copyright            |
  |    notice, this list of conditions and the following disclaimer in the          |
  |    documentation and/or other materials provided with the distribution.         |
  |                                                                                 |
  | 3. All advertising materials mentioning features or use of this software        |
  |    must display the following acknowledgement:                                  |
  |    This product includes software developed by César D. Rodas.                  |
  |                                                                                 |
  | 4. Neither the name of the César D. Rodas nor the                               |
  |    names of its contributors may be used to endorse or promote products         |
  |    derived from this software without specific prior written permission.        |
  |                                                                                 |
  | THIS SOFTWARE IS PROVIDED BY CÉSAR D. RODAS ''AS IS'' AND ANY                   |
  | EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED       |
  | WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE          |
  | DISCLAIMED. IN NO EVENT SHALL CÉSAR D. RODAS BE LIABLE FOR ANY                  |
  | DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES      |
  | (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;    |
  | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND     |
  | ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT      |
  | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS   |
  | SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE                     |
  +---------------------------------------------------------------------------------+
  | Authors: César Rodas <crodas@php.net>                                           |
  +---------------------------------------------------------------------------------+
*/

/**
 * InfluxDB PHP Client
 *
 * Modified fork of https://github.com/crodas/InfluxPHP
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 */

namespace Chromabits\InfluxClient;

/**
 * Class Client
 *
 * An InfluxDb client
 *
 * @package Chromabits\InfluxClient
 */
class Client extends BaseHttp
{
    /**
     * Construct an instance of a Client
     *
     * @param string $host
     * @param int $port
     * @param string $user
     * @param string $pass
     * @param bool $https
     */
    public function __construct($host = "localhost", $port = 8086, $user = 'root', $pass = 'root', $https = false)
    {
        parent::__construct();

        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;

        if ($https) {
            $this->protocol = 'https';
        }

        $this->setupClient();
    }

    /**
     * Delete a database from this server
     *
     * @param $name
     *
     * @return \GuzzleHttp\Message\ResponseInterface|mixed|null
     */
    public function deleteDatabase($name)
    {
        return $this->delete("db/$name");
    }

    /**
     * Create a new database
     *
     * @param $name
     *
     * @return \Chromabits\InfluxClient\Database
     */
    public function createDatabase($name)
    {
        $this->post('db', ['name' => $name]);

        return new Database($this, $name);
    }

    /**
     * Get all databases in this server
     *
     * @return Database[]
     */
    public function getDatabases()
    {
        $self = $this;

        return array_map(function ($obj) use ($self) {
            return new Database($self, $obj['name']);
        }, $this->get('db')->json());
    }

    /**
     * Get a wrapper for a database
     *
     * @param $name
     *
     * @return \Chromabits\InfluxClient\Database
     */
    public function getDatabase($name)
    {
        return new Database($this, $name);
    }

    /**
     * Magic method for accessing Database objects as properties
     *
     * @param $name
     *
     * @return \Chromabits\InfluxClient\Database
     */
    public function __get($name)
    {
        return new Database($this, $name);
    }
}
