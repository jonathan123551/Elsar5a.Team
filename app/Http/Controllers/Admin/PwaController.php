<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class PwaController extends Controller
{
    public function manifest(): Response
    {
        $path = public_path('admin-manifest.webmanifest');
        $content = is_file($path) ? (string) file_get_contents($path) : json_encode([
            'name' => 'Elsar5a Admin',
            'short_name' => 'Elsar5a',
            'start_url' => '/admin',
            'scope' => '/admin',
            'display' => 'standalone',
            'background_color' => '#070a11',
            'theme_color' => '#070a11',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response($content, 200)
            ->header('Content-Type', 'application/manifest+json; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function serviceWorker(): Response
    {
        $path = public_path('admin-sw.js');
        $content = is_file($path) ? (string) file_get_contents($path) : '';

        return response($content, 200)
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Service-Worker-Allowed', '/admin')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}
