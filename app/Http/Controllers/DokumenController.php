<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DokumenController extends Controller
{
    public function download(Request $request, string $path): StreamedResponse
    {
        // Decode path safely
        $decodedPath = base64_decode($path);

        if (!Storage::exists($decodedPath)) {
            abort(404, 'File dokumen tidak ditemukan.');
        }

        return Storage::download($decodedPath);
    }
}
