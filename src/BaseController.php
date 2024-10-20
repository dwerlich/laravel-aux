<?php

namespace LaravelAux;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

abstract class BaseController
{
    /**
     * @var BaseService
     */
    protected $service;

    /**
     * @var FormRequest
     */
    protected $request;

    /**
     * BaseController constructor.
     *
     * @param BaseService $service
     * @param FormRequest $request
     */
    public function __construct(BaseService $service, FormRequest $request)
    {
        $this->service = $service;
        $this->request = $request;
    }

    /**
     * Method to get Model Objects
     *
     * @param Request $request
     * @return Collection|static[]
     */
    public function index(Request $request)
    {
        return $this->service->get('*', $request);
    }

    /**
     * Method to Create Model Object
     *
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $this->validation();
        if ($this->service->create($request->all())) {
            return response()->json(['message' => 'Cadastro realizado!'], 201);
        }
        return response()->json(['message' => 'Não foi possível adicionar o registro'], 500);
    }

    /**
     * Method to Update Model Object
     *
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    public function update(Request $request, int $id)
    {
        $this->validation('PUT');
        if ($this->service->update($request->all(), $id)) {
            return response()->json(['message' => 'Registro atualizado'], 200);
        }
        return response()->json(['message' => 'Não foi possível atualizar o registro'], 500);
    }

    /**
     * Method to get Model Object
     *
     * @param int $id
     * @return BaseService[]|Collection
     */
    public function show(int $id)
    {
        return $this->service->show($id);
    }

    /**
     * Method to delete Model Object
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy(int $id)
    {
        if ($this->service->delete($id)) {
            return response()->json(['message' => 'Registro excluído'], 200);
        }
        return response()->json(['message' => 'Não foi possível excluir o registro'], 500);
    }

    /**
     * Method to validate Request
     *
     * @param string|null $method
     * @return void
     */
    protected function validation($method = null)
    {
        $validator = Validator::make(request()->all(), $this->request->rules($method), $this->request->messages(), $this->request->attributes());

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json($validator->errors()->toArray(), 422));
        }
    }
}
