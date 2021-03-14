<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\MsFood;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $name = $request->input('name');
        $types = $request->input('types');

        $price_from = $request->input('price_from');
        $price_to = $request->input('price_to');

        $rate_from = $request->input('rate_from');
        $rate_to = $request->input('rate_to');

        if ($id) {
            $food = MsFood::find($id);

            if ($food) {
                return ResponseFormatter::success(
                    $food,
                    'Product data is retrieved successfuly.'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Product data is not exist.',
                    404
                );
            }
        }

        $food = MsFood::query();

        if ($name) {
            $food->where('name', 'like', '%' . $name . '%');
        }
        if ($types) {
            $food->where('types', 'like', '%' . $types . '%');
        }
        if ($price_from) {
            $food->where('price_from', '>=', $price_from);
        }
        if ($price_to) {
            $food->where('price_to', '<=', $price_to);
        }
        if ($rate_from) {
            $food->where('rate_from', '>=', $rate_from);
        }
        if ($rate_to) {
            $food->where('rate_to', '<=', $rate_to);
        }

        return ResponseFormatter::success(
            $food->paginate($limit),
            'List of product data is retrieved successfuly.'
        );
    }
}
