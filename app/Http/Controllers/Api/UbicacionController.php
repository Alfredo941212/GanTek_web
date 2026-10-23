<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UbicacionController extends Controller
{
    public function estados(): JsonResponse
    {
        return response()->json(['data' => DB::table('estados')->orderBy('nombre')->get()]);
    }

    public function municipios(string $clave): JsonResponse
    {
        $estado = DB::table('estados')->where('clave_inegi', $clave)->first();
        abort_unless($estado, 404, 'El estado indicado no existe.');

        return response()->json(['data' => DB::table('municipios')->where('estado_id', $estado->id)->orderBy('nombre')->get()]);
    }

    public function localidades(Request $request, string $clave): JsonResponse
    {
        $datos = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'por_pagina' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'seleccionada' => ['nullable', 'string', 'regex:/^[0-9]{9}$/'],
        ]);
        $municipio = DB::table('municipios')->where('clave_inegi', $clave)->first();
        abort_unless($municipio, 404, 'El municipio indicado no existe.');
        $consulta = DB::table('localidades')->where('municipio_id', $municipio->id);
        if (! empty($datos['buscar'])) {
            $consulta->where('nombre', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $datos['buscar']).'%');
        }
        if (! empty($datos['seleccionada'])) {
            $consulta->where('clave_inegi', $datos['seleccionada']);
        }

        return response()->json($consulta->orderBy('nombre')->orderBy('clave_inegi')->paginate($datos['por_pagina'] ?? 25)->withQueryString());
    }
}
