<?php
/**
 * Created by PhpStorm.
 * User: sergi
 * Date: 23/10/15
 * Time: 10:04
 */
namespace SleepingOwl\Admin\Repository;


/**
 * Class BaseRepository
 * @package SleepingOwl\Admin\Repository
 */
interface BaseRepositoryInterface
{
    /**
     * Get or set eager loading relations
     * @param string|string[]|null $with
     * @return $this|string[]
     */
    public function with($with = null);

    /**
     * Get base query
     * @return mixed
     */
    public function query();

    /**
     * Find model instance by id
     * @param int $id
     * @return mixed
     */
    public function find($id);

    /**
     * Find model instances by ids
     * @param int[] $ids
     * @return mixed
     */
    public function findMany($ids);

    /**
     * Delete model instance by id
     * @param int $id
     */
    public function delete($id);

    /**
     * Force Delete model instance by id
     * @param int $id
     */
    public function forceDelete($id);

    /**
     * Restore model instance by id
     * @param int $id
     */
    public function restore($id);

    /**
     * Get or set repository related model intance
     * @param mixed|null $model
     * @return $this|mixed
     */
    public function model($model = null);

    /**
     * Check if model's table has column
     * @param string $column
     * @return bool
     */
    public function hasColumn($column);
}