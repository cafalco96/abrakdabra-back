<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDiscountCodeController extends Controller
{
    public function index()
    {
        return DiscountCode::orderByDesc('created_at')->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'       => ['required', 'string', 'max:50'],
            'type'       => ['required', 'in:percentage,fixed'],
            'value'      => ['required', 'numeric', 'min:0.01'],
            'is_active'  => ['required', 'boolean'],
            'starts_at'  => ['nullable', 'date'],
            'ends_at'    => ['nullable', 'date', 'after_or_equal:starts_at'],
            'max_uses'   => ['nullable', 'integer', 'min:1'],
        ]);

        // Normalizar el codigo a mayusculas antes de validar unicidad y guardar
        $data['code'] = strtoupper(trim($data['code']));

        // Validar unicidad despues de normalizar
        if (DiscountCode::where('code', $data['code'])->exists()) {
            return response()->json([
                'message' => 'El codigo ya existe.',
                'errors' => ['code' => ['El codigo ya existe.']],
            ], 422);
        }

        $discount = DiscountCode::create($data);

        return response()->json($discount, 201);
    }

    public function show(DiscountCode $discountCode)
    {
        return response()->json($discountCode);
    }

    public function findByCode(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        // Normalizar a mayusculas para busqueda consistente
        $code = strtoupper(trim($data['code']));

        $discount = DiscountCode::where('code', $code)->first();

        if (! $discount) {
            return response()->json(['message' => 'Codigo no encontrado'], 404);
        }

        return response()->json($discount);
    }

    public function update(Request $request, DiscountCode $discountCode)
    {
        $data = $request->validate([
            'type'      => ['sometimes', 'in:percentage,fixed'],
            'value'     => ['sometimes', 'numeric', 'min:0.01'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at'   => ['nullable', 'date', 'after_or_equal:starts_at'],
            'max_uses'  => ['nullable', 'integer', 'min:1'],
        ]);

        $discountCode->update($data);

        return response()->json($discountCode);
    }

    public function destroy(DiscountCode $discountCode)
    {
        $discountCode->delete();

        return response()->json([], 204);
    }
}
