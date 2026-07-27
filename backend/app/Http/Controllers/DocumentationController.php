<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentationController extends Controller
{
    public function ui(): Response
    {
        return response()->view('swagger-ui');
    }

    public function spec(): StreamedResponse
    {
        $path = base_path('../docs/openapi.yaml');

        abort_unless(is_file($path), 404);

        return response()->stream(function () use ($path) {
            echo file_get_contents($path);
        }, 200, [
            'Content-Type' => 'application/yaml',
        ]);
    }
}
