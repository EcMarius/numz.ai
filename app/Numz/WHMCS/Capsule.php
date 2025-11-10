<?php

namespace App\Numz\WHMCS;

use Illuminate\Database\Capsule\Manager as BaseCapsule;
use Illuminate\Support\Facades\DB;

/**
 * WHMCS Capsule Compatibility
 *
 * Provides backward compatibility for WHMCS modules that use Capsule ORM
 * This is a wrapper around Laravel's built-in Eloquent/DB facade
 */
class Capsule
{
    /**
     * Get a connection instance
     *
     * @param string|null $connection
     * @return \Illuminate\Database\Connection
     */
    public static function connection($connection = null)
    {
        return DB::connection($connection);
    }

    /**
     * Get a schema builder instance
     *
     * @param string|null $connection
     * @return \Illuminate\Database\Schema\Builder
     */
    public static function schema($connection = null)
    {
        return DB::connection($connection)->getSchemaBuilder();
    }

    /**
     * Get a table instance
     *
     * @param string $table
     * @param string|null $as
     * @param string|null $connection
     * @return \Illuminate\Database\Query\Builder
     */
    public static function table($table, $as = null, $connection = null)
    {
        return DB::connection($connection)->table($table, $as);
    }

    /**
     * Start a new database transaction
     *
     * @return void
     */
    public static function beginTransaction()
    {
        DB::beginTransaction();
    }

    /**
     * Commit the active database transaction
     *
     * @return void
     */
    public static function commit()
    {
        DB::commit();
    }

    /**
     * Rollback the active database transaction
     *
     * @return void
     */
    public static function rollback()
    {
        DB::rollBack();
    }

    /**
     * Execute a raw SQL statement
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public static function statement($query, $bindings = [])
    {
        return DB::statement($query, $bindings);
    }

    /**
     * Run a select statement against the database
     *
     * @param string $query
     * @param array $bindings
     * @return array
     */
    public static function select($query, $bindings = [])
    {
        return DB::select($query, $bindings);
    }

    /**
     * Run an insert statement against the database
     *
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public static function insert($query, $bindings = [])
    {
        return DB::insert($query, $bindings);
    }

    /**
     * Run an update statement against the database
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public static function update($query, $bindings = [])
    {
        return DB::update($query, $bindings);
    }

    /**
     * Run a delete statement against the database
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public static function delete($query, $bindings = [])
    {
        return DB::delete($query, $bindings);
    }

    /**
     * Get the PDO connection
     *
     * @return \PDO
     */
    public static function getPdo()
    {
        return DB::connection()->getPdo();
    }

    /**
     * Get last insert ID
     *
     * @return int
     */
    public static function lastInsertId()
    {
        return DB::connection()->getPdo()->lastInsertId();
    }
}

/**
 * WHMCS-style global Capsule accessor
 */
if (!class_exists('Capsule')) {
    class_alias(\App\Numz\WHMCS\Capsule::class, 'Capsule');
}
