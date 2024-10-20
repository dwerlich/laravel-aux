<?php

namespace LaravelAux;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

abstract class BaseService
{
    protected BaseRepository $repository;
    protected Request $request;
    protected $result;
    protected array $return = [];
    protected array $filtersOrder = [
        'query',
        'whereNull',
        'whereNotNull',
        'with',
        'withNotEmpty',
        'withEmpty',
        'hasRelationChildren',
        'orderBy',
        'orderByAsc',
        'orderByDesc',
        'paginated',
        'groupBy',
        'whereInColumn',
        'encrypted'
    ];

    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
        $this->filtersOrder = array_merge($this->repository->getGuarded(), $this->filtersOrder);
        $this->filtersOrder = array_merge($this->repository->getFillable(), $this->filtersOrder);
    }

    public function all()
    {
        return $this->repository->all();
    }

    public function get($columns, Request $request, $format = 'array')
    {
        if ($request->input('order')) {
            if ($request->input('order') === 'asc') {
                $request->merge(['orderByAsc' => $request->input('order_by')]);
            } else {
                $request->merge(['orderByDesc' => $request->input('order_by')]);
            }
        }
        $this->request = $request;

        $this->result = $this->repository->select($columns);

        $this->filtersOrder[] = 'created_at';
        foreach ($this->filtersOrder as $value) {
            $filter = $this->request->get($value);

            if (method_exists($this, $value) && !empty($filter)) {
                $this->$value($filter);  // Aplicar filtro na query
            } else {
                if ($this->columnExists($value) && array_key_exists($value, $this->request->all())) {
                    $type = Schema::getColumnType($this->repository->getTable(), $value);

                    if (($type == 'datetime' || $type == 'date') && strpos($filter, ',') !== false) {
                        $this->whereBetweenDate($value, $filter);
                    } else {
                        if (is_array($filter)) {
                            $this->result->whereIn($value, $filter);
                        } else {
                            $this->where($value, $filter);
                        }
                    }
                }
            }
        }

        if (!empty($this->request->get('limit'))) {
            // Paginar
            $this->result = $this->result->paginate($this->request->get('limit'));

            // Calcular informações de paginação
            $this->return['page'] = $this->result->currentPage() - 1;
            $this->return['pages'] = $this->result->lastPage();
        } else {
            // Se não houver paginação, apenas execute a query e obtenha os dados como Collection
            $this->result = $this->result->get();
        }

        // Obter os dados no formato desejado (array ou objeto)
        $array = ($format === 'array') ? $this->result->toArray() : $this->result;
        $results = (isset($array['data'])) ? $array['data'] : $array;

        // Preencher os dados de retorno
        $this->return['data'] = (!empty($results['data'])) ? $results['data'] : $results;
        $this->return['count'] = $array['total'] ?? $this->result->count();
        $this->return['filter'] = $this->result->count();
        $this->return['per_page'] = $this->request->get('limit');

        return $this->return;
    }

    private function whereBetweenDate($key, $value): void
    {
        $value = explode(',', $value);
        $this->result = $this->result->where($key, '>=', $value[0])->where($key, '<=', $value[1]);
    }

    public function find($id)
    {
        return $this->repository->find($id);
    }

    public function show($id)
    {
        return $this->repository->find($id);
    }

    public function findBy(array $data)
    {
        return $this->repository->findBy($data);
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    public function update(array $data, int $id)
    {
        $elem = $this->repository->find($id);
        if ($elem) {
            return $elem->update($data);
        }
        return false;
    }

    public function delete($id)
    {
        return $this->repository->delete($id);
    }

    private function with($value): void
    {
        $this->result = $this->repository->withRelationIfExists($this->result, $value);
    }

    private function withNotEmpty($value): void
    {
        $this->result = $this->repository->withRelationIfNotEmpty($this->result, $value);
    }

    private function withEmpty($value): void
    {
        $this->result = $this->repository->withRelationEmpty($this->result, $value);
    }

    public function orderBy($value)
    {
        if ($this->request->get('ascending')) {
            $this->result = $this->result->orderBy($value);
            return;
        }

        $this->result = $this->result->orderByDesc($value);
    }

    private function orderByAsc($value): void
    {
        if (is_array($value)) {
            foreach ($value as $filter) {
                $this->result = $this->result->orderBy($filter);
            }
            return;
        }
        $this->result = $this->result->orderBy($value);
    }

    private function orderByDesc($value): void
    {
        if (is_array($value)) {
            foreach ($value as $filter) {
                $this->result = $this->result->orderByDesc($filter);
            }
            return;
        }
        $this->result = $this->result->orderByDesc($value);
    }

    private function columnExists($value): bool
    {
        return Schema::hasColumn($this->repository->getTable(), $value);
    }

    private function where($key, $value): void
    {
        if (strpos($value, ',')) {
            $value = explode(',', $value);
        }

        $this->result = $this->result->where(function ($query) use ($key, $value) {
            if ($encryptedProperties = $this->request->get('encrypted')) {
                foreach ($encryptedProperties as $property) {
                    if ($property == $key) {
                        $query->whereEncrypted($key, 'LIKE', '%' . $value . '%');
                        return;
                    }
                }
            }

            if (is_array($value)) {
                $query->whereIn($key, $value);
                return;
            }
            if (is_numeric($value)) {
                $query->where($key, $value);
            } else {
                $query->whereRaw("LOWER({$key}) LIKE LOWER(?)", '%' . $value . '%');
            }
        });
    }

    private function whereNull($key)
    {
        $this->result = $this->result->whereNull($key);
    }

    private function whereNotNull($key)
    {
        $this->result = $this->result->whereNotNull($key);
    }

    private function hasRelationChildren($key)
    {
        $this->result = $this->result->has($key);
    }

    private function query($value): void
    {
        $columns = $this->repository->getFillable();
        foreach ($columns as $column) {
            $type = Schema::getColumnType($this->repository->getTable(), $column);
            if (!in_array($type, ['integer', 'boolean', 'decimal'])) {
                $this->result = $this->result->orWhereRaw("LOWER({$column}) LIKE LOWER('%{$value}%')");
            } else {
                if (is_numeric($value) || is_bool($value)) {
                    $this->result = $this->result->orWhere($column, $value);
                }
            }
        }
    }

    private function groupBy($column): void
    {
        $this->result = $this->result->groupBy($column);
    }

    private function whereInColumn($value): void
    {
        $string = explode('[', $value);
        $column = $string[0];
        $value = substr($string[1], 0, -1);
        $value = explode(',', $value);
        $this->result = $this->result->whereIn($column, $value);
    }
}
