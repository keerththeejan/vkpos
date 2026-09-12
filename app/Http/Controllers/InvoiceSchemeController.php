<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\InvoiceLayout;
use App\InvoiceScheme;
use Datatables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvoiceSchemeController extends Controller
{
    protected $number_types;

    public function __construct()
    {
        $this->number_types = ['sequential' => __('invoice.sequential'), 'random'=> __('invoice.random')];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('invoice_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $schemes = InvoiceScheme::where('business_id', $business_id)
                            ->select(['id', 'name', 'scheme_type', 'prefix', 'number_type', 'start_number', 'invoice_count', 'total_digits', 'is_default']);

            $number_type = request()->get('filter_number_type');
            if (! empty($number_type) && array_key_exists($number_type, $this->number_types)) {
                $schemes->where('number_type', $number_type);
            }

            $is_default = request()->get('filter_is_default');
            if ($is_default !== null && $is_default !== '') {
                $schemes->where('is_default', (int) $is_default);
            }

            return Datatables::of($schemes)
                ->addColumn(
                    'action',
                    '<div class="is-row-actions">
                        <button type="button" data-href="{{action(\'App\Http\Controllers\InvoiceSchemeController@show\', [$id])}}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-accent btn-modal" data-container=".invoice_view_modal" title="@lang(\'messages.view\')"><i class="fa fa-eye"></i> @lang("messages.view")</button>
                        <button type="button" data-href="{{action(\'App\Http\Controllers\InvoiceSchemeController@edit\', [$id])}}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary btn-modal" data-container=".invoice_edit_modal" title="@lang(\'messages.edit\')"><i class="fa fa-edit"></i> @lang("messages.edit")</button>
                        <button type="button" data-href="{{action(\'App\Http\Controllers\InvoiceSchemeController@destroy\', [$id])}}" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs tw-dw-btn-error delete_invoice_button" @if($is_default) disabled @endif title="@lang(\'messages.delete\')"><i class="fa fa-trash"></i> @lang("messages.delete")</button>
                        @if($is_default)
                            <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-accent" disabled><i class="fa fa-check-square-o" aria-hidden="true"></i> @lang("barcode.default")</button>
                        @else
                            <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info set_default_invoice" data-href="{{action(\'App\Http\Controllers\InvoiceSchemeController@setDefault\', [$id])}}">@lang("barcode.set_as_default")</button>
                        @endif
                    </div>'
                )
                ->editColumn('number_type', function ($row) {
                    return $this->number_types[$row->number_type] ?? $row->number_type;
                })
                ->editColumn('prefix', function ($row) {
                    if ($row->scheme_type == 'year') {
                        return $row->prefix.date('Y').config('constants.invoice_scheme_separator');
                    } else {
                        return $row->prefix;
                    }
                })
                ->editColumn('invoice_count', function ($row) {
                    $count = (int) $row->invoice_count;
                    if (($row->number_type ?? 'sequential') === 'sequential') {
                        $next = $this->previewNextCount($row);

                        return '<span class="is-count">'.$count.'</span> <span class="is-next-hint">'.e(__('invoice.next_number')).': '.$next.'</span>';
                    }

                    return '<span class="is-count">'.$count.'</span>';
                })
                ->editColumn('is_default', function ($row) {
                    if ((int) $row->is_default === 1) {
                        return '<span class="is-status is-status-default"><span class="is-dot" aria-hidden="true"></span> '.e(__('barcode.default')).'</span>';
                    }

                    return '<span class="is-status is-status-standard"><span class="is-dot" aria-hidden="true"></span> '.e(__('invoice.standard')).'</span>';
                })
                ->editColumn('name', function ($row) {
                    $name = e($row->name);
                    if ($row->is_default == 1) {
                        return $name.' &nbsp; <span class="label label-success">'.e(__('barcode.default')).'</span>';
                    }

                    return $name;
                })
                ->removeColumn('id')
                ->removeColumn('scheme_type')
                ->rawColumns(['name', 'invoice_count', 'is_default', 'action'])
                ->make(true);
        }

        $invoice_layouts = InvoiceLayout::where('business_id', $business_id)
                                        ->with(['locations'])
                                        ->get();

        $number_types = $this->number_types;

        return view('invoice_scheme.index')
                    ->with(compact('invoice_layouts', 'number_types'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('invoice_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $number_types = $this->number_types;
        return view('invoice_scheme.create')->with(compact('number_types'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('invoice_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $validator = Validator::make($request->all(), $this->schemeRules());
            if ($validator->fails()) {
                return ['success' => false, 'msg' => $validator->errors()->first()];
            }

            $input = $this->sanitizeSchemeInput($request);
            $business_id = $request->session()->get('user.business_id');
            $input['business_id'] = $business_id;

            DB::transaction(function () use ($input, $request, $business_id) {
                if (! empty($request->input('is_default'))) {
                    InvoiceScheme::where('business_id', $business_id)
                                ->where('is_default', 1)
                                ->update(['is_default' => 0]);
                    $input['is_default'] = 1;
                }
                InvoiceScheme::create($input);
            });

            $output = ['success' => true,
                'msg' => __('invoice.added_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (! auth()->user()->can('invoice_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $invoice = InvoiceScheme::where('business_id', $business_id)->findOrFail($id);
        $number_types = $this->number_types;
        $next_count = $this->previewNextCount($invoice);
        $used_locations = $this->locationNamesUsingScheme($business_id, $invoice->id);

        return view('invoice_scheme.show')
            ->with(compact('invoice', 'number_types', 'next_count', 'used_locations'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('invoice_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $invoice = InvoiceScheme::where('business_id', $business_id)->findOrFail($id);

        $number_types = $this->number_types;

        return view('invoice_scheme.edit')
            ->with(compact('invoice', 'number_types'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('invoice_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $validator = Validator::make($request->all(), $this->schemeRules());
            if ($validator->fails()) {
                return ['success' => false, 'msg' => $validator->errors()->first()];
            }

            $input = $this->sanitizeSchemeInput($request);
            $business_id = $request->session()->get('user.business_id');

            $scheme = InvoiceScheme::where('business_id', $business_id)
                ->where('id', $id)
                ->first();

            if (empty($scheme)) {
                $output = ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            } else {
                $scheme->update($input);
                $output = ['success' => true,
                    'msg' => __('invoice.updated_success'),
                ];
            }
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('invoice_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');
                $invoice = InvoiceScheme::where('business_id', $business_id)->find($id);

                if (empty($invoice)) {
                    $output = ['success' => false,
                        'msg' => __('messages.something_went_wrong'),
                    ];
                } elseif ($invoice->is_default == 1) {
                    $output = ['success' => false,
                        'msg' => __('invoice.cannot_delete_default'),
                    ];
                } elseif ($this->schemeIsAssignedToLocation($business_id, $invoice->id)) {
                    $output = ['success' => false,
                        'msg' => __('invoice.cannot_delete_in_use'),
                    ];
                } else {
                    $invoice->delete();
                    $output = ['success' => true,
                        'msg' => __('invoice.deleted_success'),
                    ];
                }
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    /**
     * Sets invoice scheme setting as default
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function setDefault($id)
    {
        if (! auth()->user()->can('invoice_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');
                $invoice = InvoiceScheme::where('business_id', $business_id)->find($id);

                if (empty($invoice)) {
                    $output = ['success' => false,
                        'msg' => __('messages.something_went_wrong'),
                    ];
                } else {
                    DB::transaction(function () use ($business_id, $invoice) {
                        InvoiceScheme::where('business_id', $business_id)
                                        ->where('is_default', 1)
                                        ->update(['is_default' => 0]);

                        $invoice->is_default = 1;
                        $invoice->save();
                    });

                    $output = ['success' => true,
                        'msg' => __('barcode.default_set_success'),
                    ];
                }
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    /**
     * Validation rules for scheme create/update. invoice_count is never accepted.
     */
    private function schemeRules()
    {
        return [
            'name' => 'required|string|max:191',
            'scheme_type' => 'required|in:blank,year',
            'prefix' => 'nullable|string|max:191',
            'number_type' => 'required|in:sequential,random,aleatory',
            'start_number' => 'nullable|integer|min:0',
            'total_digits' => 'required|integer|in:4,5,6,7,8,9,10',
        ];
    }

    /**
     * Allowed writable fields only. Never touches invoice_count or historical invoices.
     */
    private function sanitizeSchemeInput(Request $request)
    {
        $input = $request->only(['name', 'scheme_type', 'prefix', 'start_number', 'total_digits', 'number_type']);

        if (($input['number_type'] ?? '') === 'aleatory') {
            $input['number_type'] = 'random';
        }

        $is_random = ($input['number_type'] ?? '') === 'random';
        if ($is_random) {
            $input['start_number'] = $input['start_number'] === '' || $input['start_number'] === null
                ? 0
                : $input['start_number'];
        } else {
            $input['start_number'] = $input['start_number'] === '' || $input['start_number'] === null
                ? 0
                : $input['start_number'];
        }

        $input['prefix'] = $input['prefix'] ?? '';
        unset($input['invoice_count'], $input['is_default'], $input['business_id']);

        return $input;
    }

    /**
     * Display-only next sequential count. Mirrors getInvoiceNumber math without incrementing.
     */
    private function previewNextCount($row)
    {
        if (($row->number_type ?? 'sequential') !== 'sequential') {
            return '—';
        }

        $next = (int) $row->start_number + (int) $row->invoice_count;
        $digits = (int) ($row->total_digits ?: 4);

        return str_pad((string) $next, $digits, '0', STR_PAD_LEFT);
    }

    private function schemeIsAssignedToLocation($business_id, $scheme_id)
    {
        return BusinessLocation::where('business_id', $business_id)
            ->where(function ($query) use ($scheme_id) {
                $query->where('invoice_scheme_id', $scheme_id)
                    ->orWhere('sale_invoice_scheme_id', $scheme_id);
            })
            ->exists();
    }

    private function locationNamesUsingScheme($business_id, $scheme_id)
    {
        return BusinessLocation::where('business_id', $business_id)
            ->where(function ($query) use ($scheme_id) {
                $query->where('invoice_scheme_id', $scheme_id)
                    ->orWhere('sale_invoice_scheme_id', $scheme_id);
            })
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }
}
