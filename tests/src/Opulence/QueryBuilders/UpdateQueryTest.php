<?php
/**
 * Opulence
 *
 * @link      https://www.opulencephp.com
 * @copyright Copyright (C) 2016 David Young
 * @license   https://github.com/opulencephp/Opulence/blob/master/LICENSE.md
 */
namespace Opulence\QueryBuilders;

use PDO;

/**
 * Tests the update query
 */
class UpdateQueryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tests adding more columns
     */
    public function testAddingMoreColumns()
    {
        $query = new UpdateQuery("users", "", ["name" => "david"]);
        $query->addColumnValues(["email" => "bar@foo.com"]);
        $this->assertEquals("UPDATE users SET name = ?, email = ?", $query->getSql());
        $this->assertEquals([
            ["david", PDO::PARAM_STR],
            ["bar@foo.com", PDO::PARAM_STR]
        ], $query->getParameters());
    }

    /**
     * Tests a basic query
     */
    public function testBasicQuery()
    {
        $query = new UpdateQuery("users", "", ["name" => "david"]);
        $this->assertEquals("UPDATE users SET name = ?", $query->getSql());
        $this->assertEquals([
            ["david", PDO::PARAM_STR]
        ], $query->getParameters());
    }

    /**
     * Tests all the methods in a single, complicated query
     */
    public function testEverything()
    {
        $query = new UpdateQuery("users", "u", ["name" => "david"]);
        $query->addColumnValues(["email" => "bar@foo.com"])
            ->where("u.id = ?", "emails.userid = u.id", "emails.email = ?")
            ->orWhere("u.name = ?")
            ->andWhere("subscriptions.userid = u.id", "subscriptions.type = 'customer'")
            ->addUnnamedPlaceholderValues([[18175, PDO::PARAM_INT], "foo@bar.com", "dave"]);
        $this->assertEquals("UPDATE users AS u SET name = ?, email = ? WHERE (u.id = ?) AND (emails.userid = u.id) AND (emails.email = ?) OR (u.name = ?) AND (subscriptions.userid = u.id) AND (subscriptions.type = 'customer')",
            $query->getSql());
        $this->assertEquals([
            ["david", PDO::PARAM_STR],
            ["bar@foo.com", PDO::PARAM_STR],
            [18175, PDO::PARAM_INT],
            ["foo@bar.com", PDO::PARAM_STR],
            ["dave", PDO::PARAM_STR]
        ], $query->getParameters());
    }

    /**
     * Tests adding a "WHERE" clause
     */
    public function testWhere()
    {
        $query = new UpdateQuery("users", "", ["name" => "david"]);
        $query->where("id = ?")
            ->addUnnamedPlaceholderValue(18175, PDO::PARAM_INT);
        $this->assertEquals("UPDATE users SET name = ? WHERE (id = ?)", $query->getSql());
        $this->assertEquals([
            ["david", PDO::PARAM_STR],
            [18175, PDO::PARAM_INT]
        ], $query->getParameters());
    }
} 