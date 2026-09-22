<?php

namespace App\Http\Controllers;

use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class PerformanceController extends Controller
{
    protected $commonUtil;

    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    public function clearCache(Request $request)
    {
        if (! $this->commonUtil->is_admin(auth()->user())) {
            abort(403, 'Unauthorized action.');
        }

        Artisan::call('vkpos:clear-cache', ['--opcache' => true]);

        $message = 'Application cache cleared.';
        if ($request->ajax()) {
            return [
                'success' => true,
                'msg' => $message,
            ];
        }

        return redirect()->back()->with('status', [
            'success' => 1,
            'msg' => $message,
        ]);
    }
}
