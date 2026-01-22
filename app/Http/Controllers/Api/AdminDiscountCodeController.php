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
            'code'      => ['required', 'string', 'max:50', 'unique:discount_codes,code'],
            'type'      => ['required', 'in:percentage,fixed'],
            'value'     => ['required', 'numeric', 'min:0.01'],
            'is_active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at'   => ['nullable', 'date', 'after_or_equal:starts_at'],
            'max_uses'  => ['nullable', 'integer', 'min:1'],
        ]);

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

        $code = $data['code']; // sin strtoupper

        // Case-sensitive lookup using BINARY
        $discount = DiscountCode::whereRaw('BINARY `code` = ?', [$code])->first();

        if (! $discount) {
            return response()->json(['message' => 'Código no encontrado'], 404);
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
