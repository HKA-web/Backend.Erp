<?php

namespace App\Http\Controllers;

use App\Services\OpenApiGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiDocumentationController extends Controller
{
    /**
     * The OpenAPI generator service.
     *
     * @var OpenApiGeneratorService
     */
    protected OpenApiGeneratorService $generator;

    /**
     * Create a new controller instance.
     *
     * @param OpenApiGeneratorService $generator
     * @return void
     */
    public function __construct(OpenApiGeneratorService $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Display the API documentation page with Swagger UI.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('api-docs.index');
    }

    /**
     * Get the OpenAPI JSON specification for all modules.
     *
     * @return JsonResponse
     */
    public function json(): JsonResponse
    {
        $docs = $this->generator->loadFromAllModules();
        return response()->json($docs);
    }

    /**
     * Get the OpenAPI JSON specification for a specific module.
     *
     * @param string $module
     * @return JsonResponse
     */
    public function moduleJson(string $module): JsonResponse
    {
        $docs = $this->generator->loadFromModule($module);

        if ($docs === null) {
            return response()->json(['error' => 'Module documentation not found'], 404);
        }

        return response()->json($docs);
    }
}
