<?php
/**
 * Copyright (C) 2014 David Young
 *
 * Defines the skeleton for repository classes that use Redis with a PostgreSQL backup
 */
namespace RamODev\Application\Shared\Repositories;
use RamODev\Application\Shared\Databases\NoSQL\Redis;
use RamODev\Application\Shared\Databases\SQL;

abstract class RedisWithPostgreSQLBackupRepo implements IRedisWithSQLBackupRepo
{
    /** @var RedisRepo The Redis repository to use for temporary storage */
    protected $redisRepo = null;
    /** @var PostgreSQLRepo The SQL database repository to use for permanent storage */
    protected $postgreSQLRepo = null;

    /**
     * @param Redis\Database $redisDatabase The Redis database used in the repo
     * @param SQL\Database $sqlDatabase The relational database used in the repo
     */
    public function __construct(Redis\Database $redisDatabase, SQL\Database $sqlDatabase)
    {
        $this->redisRepo = $this->getRedisRepo($redisDatabase);
        $this->postgreSQLRepo = $this->getPostgreSQLRepo($sqlDatabase);
    }

    /**
     * Synchronizes the Redis repository with the SQL repository
     *
     * @return bool True if successful, otherwise false
     */
    abstract public function sync();

    /**
     * In the case we're getting data and didn't find it in the Redis repo, we need a way to store it there for future use
     * The contents of this method should call the appropriate method to store data in the Redis repo
     *
     * @param mixed $data The data to write to the Redis repository
     * @param array $funcArgs The array of function arguments to pass into the method that adds the data to the Redis repo
     */
    abstract protected function addDataToRedisRepo(&$data, $funcArgs = array());

    /**
     * Gets a SQL repo to use in this repo
     *
     * @param SQL\Database $sqlDatabase The SQL database used in the repo
     * @return PostgreSQLRepo The SQL repo to use
     */
    abstract protected function getPostgreSQLRepo(SQL\Database $sqlDatabase);

    /**
     * Gets a Redis repo to use in this repo
     *
     * @param Redis\Database $redisDatabase The Redis database used in the repo
     * @return RedisRepo The Redis repo to use
     */
    abstract protected function getRedisRepo(Redis\Database $redisDatabase);

    /**
     * Attempts to retrieve data from the Redis repo before resorting to a SQL database
     *
     * @param string $funcName The name of the method we want to call on our sub-repo classes
     * @param array $getFuncArgs The array of function arguments to pass in to our data retrieval functions
     * @param bool $addDataToRedisOnMiss True if we want to add data from the database to cache in case of a cache miss
     * @param array $setFuncArgs The array of function arguments to pass into the data set functions in the case of a Redis repo miss
     * @return mixed|bool The data from the repository if it was found, otherwise false
     */
    protected function read($funcName, $getFuncArgs = array(), $addDataToRedisOnMiss = true, $setFuncArgs = array())
    {
        // Always attempt to retrieve from the Redis repo first
        $data = call_user_func_array(array($this->redisRepo, $funcName), $getFuncArgs);

        // If we have to go off to the SQL repo
        if($data === false)
        {
            $data = call_user_func_array(array($this->postgreSQLRepo, $funcName), $getFuncArgs);

            // Try to store the data back to the Redis repo
            if($data === false)
            {
                return false;
            }

            if($addDataToRedisOnMiss)
            {
                if(is_array($data))
                {
                    foreach($data as $datum)
                    {
                        call_user_func_array(array($this, "addDataToRedisRepo"), array_merge(array(&$datum), $setFuncArgs));
                    }
                }
                else
                {
                    call_user_func_array(array($this, "addDataToRedisRepo"), array_merge(array(&$data), $setFuncArgs));
                }
            }
        }

        return $data;
    }

    /**
     * Attempts to store/change data in the repos
     * This method should be called by subclasses to perform CREATE/UPDATE/DELETE-type actions
     *
     * @param string $funcName The name of the method we want to call on our sub-repo classes
     * @param array $funcArgs The array of function arguments to pass in
     * @return bool True if successful, otherwise false
     */
    protected function write($funcName, $funcArgs)
    {
        // We update the SQL repo first in the case that it sets an SQL row Id to the object
        return call_user_func_array(array($this->postgreSQLRepo, $funcName), $funcArgs) && call_user_func_array(array($this->redisRepo, $funcName), $funcArgs);
    }
} 