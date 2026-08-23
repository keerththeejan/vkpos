<?php

namespace App\Http\Controllers;

use App\ProductSerialNumber;
use App\Utils\ProductSerialNumberUtil;
use Illuminate\Http\Request;

class ProductSerialNumberController extends Controller
{
    protected $serialUtil;

    public function __construct(ProductSerialNumberUtil $serialUtil)
    {
        $this->serialUtil = $serialUtil;
    }

    /**
     * Live validate a serial against stock.
     */
    public function check(Request $request)
    {
        if (! auth()->user()->can('purchase.create') && ! auth()->user()->can('purchase.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $result = $this->serialUtil->checkSerialAvailability(
            $request->input('serial_number'),
            $business_id,
            $request->input('purchase_id'),
            $request->input('product_id')
        );

        return response()->json($result);
    }

    /**
     * Available serials for POS sale of a product.
     */
    public function available(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $product_id = $request->input('product_id');
        $variation_id = $request->input('variation_id');
        $location_id = $request->input('location_id');

        if (empty($product_id)) {
            return response()->json(['serials' => []]);
        }

        $serials = $this->serialUtil->getAvailableSerials(
            $product_id,
            $business_id,
            $location_id,
            $variation_id
        );

        return response()->json([
            'serials' => $serials->map(function ($s) {
                return [
                    'id' => $s->id,
                    'serial_number' => $s->serial_number,
                ];
            })->values(),
        ]);
    }

    /**
     * Serials linked to a purchase (edit bootstrap).
     */
    public function forPurchase(Request $request, $purchase_id)
    {
        if (! auth()->user()->can('purchase.view') && ! auth()->user()->can('purchase.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $rows = ProductSerialNumber::where('purchase_id', $purchase_id)
            ->where('business_id', $business_id)
            ->orderBy('id')
            ->get(['id', 'purchase_line_id', 'product_id', 'variation_id', 'serial_number', 'status']);

        return response()->json(['serials' => $rows]);
    }
}
