<?php

namespace LaravelAux;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Method to get all Model Objects
     *
     * @return \Illuminate\Database\Eloquent\Collection|Model[]
     */
    public function all()
    {
        return $this->model->all();
    }

    /**
     * Method to get Model Object by id
     *
     * @param integer $id
     * @return mixed
     */
    public function find(int $id)
    {
        return $this->model->find($id);
    }

    /**
     * Method to get Model Object by passed Params
     *
     * @param array $data
     * @return mixed
     */
    public function findBy(array $data)
    {
        $query = $this->model->newQuery();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $query = $query->whereIn($key, $value);
            } else {
                $query = $query->where($key, $value);
            }
        }
        return $query->first();
    }

    /**
     * Method to get Model Objects by passed between dates
     *
     * @param string $key
     * @param array $value
     * @return mixed
     */
    public function whereBetween(string $key, array $value)
    {
        return $this->model::whereBetween($key, $value)->get();
    }

    /**
     * Method to get Model Object with Relations
     *
     * @param array $relations
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Builder|Model|null|object
     */
    public function with(array $relations, int $id = null)
    {
        if ($id) {
            return $this->model::with($relations)->where('id', $id)->first();
        }
        return $this->model::with($relations)->get();
    }

    /**
     * Method to get passed Model Objects Attributes
     *
     * @param string $columns
     * @return mixed
     */
    public function select($columns = '*')
    {
        return $this->model->select($columns);
    }

    /**
     * Method to Create Model Object
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Method to Get the first occurrence or Create an Model Object if that doesn't exist
     *
     * @param array $condition
     * @param array $data
     * @return mixed
     */
    public function firstOrCreate(array $condition, array $data)
    {
        return $this->model->firstOrCreate($condition, $data);
    }

    /**
     * Method to Get the first occurrence or Create an Model Object if that doesn't exist (without persisting)
     *
     * @param array $condition
     * @param array $data
     * @return mixed
     */
    public function firstOrNew(array $condition, array $data)
    {
        return $this->model->firstOrNew($condition, $data);
    }

    /**
     * Method to update Model Object
     *
     * @param array $data
     * @param int $id
     * @return mixed
     */
    public function update(array $data, int $id)
    {
        $model = $this->model->find($id);
        return $model ? $model->update($data) : null;
    }

    /**
     * Method to delete Model Object
     *
     * @param int $id
     * @return mixed
     */
    public function delete(int $id)
    {
        return $this->model->find($id)?->delete();
    }

    /**
     * Method to get Model Objects count
     *
     * @return mixed
     */
    public function count()
    {
        return $this->model->count();
    }

    /**
     * Method to get Model Object Attributes
     *
     * @return array
     */
    public function getGuarded()
    {
        return $this->model->getGuarded();
    }

    /**
     * Method to get Model Object Attributes
     *
     * @return array
     */
    public function getFillable()
    {
        return $this->model->getFillable();
    }

    /**
     * Method to get Model Table
     *
     * @return string
     */
    public function getTable()
    {
        return $this->model->getTable();
    }

    /**
     * Method to Eager Load if Relation exists
     *
     * @param $query
     * @param $relations
     * @return mixed
     * @throws \ReflectionException
     */
    public function withRelationIfExists($query, $relations)
    {
        return $this->filterRelations($query, $relations);
    }

    /**
     * Method to Eager Load if Relation exists and isn't empty
     *
     * @param $query
     * @param $relations
     * @return mixed
     * @throws \ReflectionException
     */
    public function withRelationIfNotEmpty($query, $relations)
    {
        return $this->filterRelations($query, $relations, true);
    }

    /**
     * Method to get rows who don't have relation (It's empty)
     *
     * @param $query
     * @param $relations
     * @return mixed
     * @throws \ReflectionException
     */
    public function withRelationEmpty($query, $relations)
    {
        return $this->filterRelations($query, $relations, false, true);
    }

    /**
     * Helper to filter relations dynamically
     *
     * @param $query
     * @param $relations
     * @param bool $notEmpty
     * @param bool $empty
     * @return mixed
     * @throws \ReflectionException
     */
    private function filterRelations($query, $relations, $notEmpty = false, $empty = false)
    {
        $reflection = new \ReflectionClass($this->model);
        $allRelations = array_column($reflection->getMethods(), 'name');

        if (is_array($relations)) {
            foreach ($relations as $relation) {
                if (!in_array($relation, $allRelations) && !str_contains($relation, '.')) {
                    continue;
                }
                if ($empty) {
                    $query->doesnthave($relation);
                } elseif ($notEmpty) {
                    $query->has($relation)->with($relation);
                } else {
                    $query->with($relation);
                }
            }
        } else {
            if (!in_array($relations, $allRelations)) {
                return $query;
            }
            if ($empty) {
                $query->doesnthave($relations);
            } elseif ($notEmpty) {
                $query->has($relations)->with($relations);
            } else {
                $query->with($relations);
            }
        }
        return $query;
    }

    /**
     * Method to filter in Children Table - Used only in Abstract Repository
     *
     * @param $query
     * @param $key
     * @param $value
     * @return mixed
     */
    private function childrenWhere($query, $key, $value, $relation = null)
    {
        return $query->where(function ($subquery) use ($query, $key, $value, $relation) {
            if (is_array($value)) {
                foreach ($value as $column => $condition) {
                    $subquery->whereRaw("LOWER({$key}) LIKE LOWER(?)", '%' . $condition . '%');
                }
                return $query;
            }

            if ($relation) {
                try {
                    $relationModel = $this->model->{$relation}(); // Returns a Relations subclass
                    $relatedModel = $relationModel->getRelated(); // Returns a new empty Model
                    $tableName = $relatedModel->getTable();

                    $type = DB::connection()->getDoctrineColumn($tableName, $key)->getType()->getName();

                } catch (Exception $e) {
                    $type = null;
                }

                if (isset($type) && $type == 'integer') {
                    $subquery->whereRaw($key, $value);
                } else {
                    $subquery->whereRaw("LOWER({$key}) LIKE LOWER(?)", '%' . $value . '%');
                }
            }
        });
    }
}
