<?php

namespace App\Http\Controllers;

use App\Support\DemoPayloads;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'examples' => DemoPayloads::all(),
            'llmEnabled' => filled(config('services.gemini.key')),
        ]);
    }
}
