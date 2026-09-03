<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

    public function view(Request $request, string $path): BinaryFileResponse
    {
        // Decode path safely
        $decodedPath = base64_decode($path);

        if (!Storage::exists($decodedPath)) {
            abort(404, 'File dokumen tidak ditemukan.');
        }

        $fullPath = Storage::path($decodedPath);
        $mimeType = Storage::mimeType($decodedPath) ?: 'application/pdf';

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($decodedPath) . '"',
        ]);
    }
}
