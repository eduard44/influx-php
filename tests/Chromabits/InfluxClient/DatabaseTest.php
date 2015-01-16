<?php

namespace Tests\Chromabits\InfluxClient;

use RuntimeException;
use InvalidArgumentException;
use Chromabits\InfluxClient\Client;
use Chromabits\InfluxClient\Database as DB;

/**
 * Class DatabaseTest
 */
class DatabaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = new Client('localhost', 8086, 'root', 'root');

        /**
         * Drop all testing tables
         */
        foreach ($this->client->getDatabases() as $db) {
            if (preg_match("/^test_/", $db->getName())) {
                $db->drop();
            }
        }
    }

    public function testCreate()
    {
        return $this->client->createDatabase("test_foobar");
    }

//    /**
//     *  @expectedException RuntimeException
//     */
//    public function testCreateException()
//    {
//        return $this->client->createDatabase("test_foobar");
//    }

    /**
     *  @dependsOn testCreateException
     */
    public function testDelete()
    {
        $this->client->createDatabase('test_foobar');

        return $this->client->deleteDatabase("test_foobar");
    }

    /**
     *  @dependsOn testDelete
     *  @expectedException RuntimeException
     */
    public function testDeleteException()
    {
        return $this->client->deleteDatabase("test_foobar");
    }

    public function testDatabaseObject()
    {
        $this->client->createDatabase("test_xxx");

        $this->assertTrue($this->client->test_xxx instanceof DB);
        $this->assertTrue($this->client->getDatabase("test_xxx") instanceof DB);

        $this->client->test_xxx->drop();
    }

    public function testTimePrecision()
    {
        $this->assertEquals('s', $this->client->getTimePrecision());
        $database = $this->client->createDatabase("test_yyyy");
        $this->assertEquals('s', $database->getTimePrecision());


        $this->client->setTimePrecision('m');
        $this->assertEquals('m', $this->client->getTimePrecision());
        $this->assertEquals('m', $database->getTimePrecision());

        $this->client->createDatabase("test_yyyx");
        $this->assertEquals('m', $database->getTimePrecision());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidTimePrecision()
    {
        $this->client->setTimePrecision([]);
    }

    public function testQuery()
    {
        $database = $this->client->createDatabase("test_xxx");
        $database->createUser("root", "root");

        $database->insert("foobar", ['type' => '/foobar', 'karma' => 10]);
        $database->insert("foobar", ['type' => '/foobar', 'karma' => 20]);
        $database->insert("foobar", ['type' => '/barfoo', 'karma' => 30]);

        $this->assertEquals($database->first("SELECT max(karma) FROM foobar")->max, 30);
        $this->assertEquals($database->first("SELECT min(karma) FROM foobar")->min, 10);
        $this->assertEquals($database->first("SELECT mean(karma) FROM foobar")->mean, 20);

        foreach ($database->query("SELECT mean(karma) FROM foobar GROUP BY type") as $row) {
            $this->assertTrue(is_int($row->time));
            if ($row->type == "/foobar") {
                $this->assertEquals(15, $row->mean);
            } else {
                $this->assertEquals(30, $row->mean);
            }
        }
    }

    /** @dependsOn testQuery */
    public function testDifferentTimePeriod()
    {
        $this->client->createDatabase('test_xxx');

        $database = $this->client->test_xxx;

        $database->createUser("root", "root");

        $database->insert("foobar", ['type' => '/foobar', 'karma' => 10]);
        $database->insert("foobar", ['type' => '/foobar', 'karma' => 20]);
        $database->insert("foobar", ['type' => '/barfoo', 'karma' => 30]);

        $this->client->setTimePrecision('u');
        foreach ($database->query("SELECT mean(karma) FROM foobar GROUP BY type, time(1h)") as $row) {
            $this->assertTrue($row->time > time()*1000);
        }

        $this->client->setTimePrecision('m');
        foreach ($database->query("SELECT mean(karma) FROM foobar GROUP BY type, time(1h)") as $row) {
            $this->assertTrue($row->time < time()*10000);
        }

        $this->client->setTimePrecision('s');
        foreach ($database->query("SELECT mean(karma) FROM foobar GROUP BY type, time(1h)") as $row) {
            $this->assertTrue($row->time < time()+20);
        }

        $database->drop();
    }
}
