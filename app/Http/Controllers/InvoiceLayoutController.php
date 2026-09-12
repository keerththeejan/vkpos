<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\InvoiceLayout;
use App\Transaction;
use App\Utils\BusinessUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Validator;

class InvoiceLayoutController extends Controller
{
    protected $commonUtil;

    protected $transactionUtil;

    protected $businessUtil;

    public function __construct(TransactionUtil $transactionUtil, BusinessUtil $businessUtil)
    {
        $this->commonUtil = $transactionUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return redirect('invoice-schemes');
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

        $designs = $this->getDesigns();
        $common_settings = session()->get('business.common_settings');
        $is_warranty_enabled = ! empty($common_settings['enable_product_warranty']) ? true : false;

        return view('invoice_layout.create')->with(compact('designs', 'is_warranty_enabled'));
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

        $validator = Validator::make($request->all(), $this->layoutFileRules());
        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->with('status', ['success' => 0, 'msg' => $validator->errors()->first()]);
        }

        try {
            $input = $this->layoutFormInput($request);
            $business_id = $request->session()->get('user.business_id');
            $input['business_id'] = $business_id;

            $this->applyUploadedFiles($request, $input);

            if (! empty($request->input('is_default'))) {
                InvoiceLayout::where('business_id', $business_id)
                                ->where('is_default', 1)
                                ->update(['is_default' => 0]);
                $input['is_default'] = 1;
            }

            InvoiceLayout::create($input);
            $output = ['success' => 1,
                'msg' => __('invoice.layout_added_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect('invoice-schemes')->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\InvoiceLayout  $invoiceLayout
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return redirect()->action([self::class, 'edit'], [$id]);
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
        $invoice_layout = InvoiceLayout::where('business_id', $business_id)->findOrFail($id);

        $invoice_layout->module_info = json_decode($invoice_layout->module_info, true);
        $tax_headings = ! empty($invoice_layout->table_tax_headings) ? json_decode($invoice_layout->table_tax_headings) : ['', '', '', ''];
        if (! is_array($tax_headings)) {
            $tax_headings = ['', '', '', ''];
        }
        $invoice_layout->table_tax_headings = array_pad(array_values($tax_headings), 4, '');
        if (! is_array($invoice_layout->common_settings)) {
            $invoice_layout->common_settings = [];
        }
        if (! is_array($invoice_layout->qr_code_fields)) {
            $invoice_layout->qr_code_fields = [];
        }

        $designs = $this->getDesigns();
        $preview_url = action([self::class, 'preview'], [$invoice_layout->id]);

        return view('invoice_layout.edit')
                ->with(compact('invoice_layout', 'designs', 'preview_url'));
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

        $validator = Validator::make($request->all(), $this->layoutFileRules());
        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => 0, 'msg' => $validator->errors()->first()]);
            }

            return redirect()->back()
                ->withInput()
                ->with('status', ['success' => 0, 'msg' => $validator->errors()->first()]);
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $layout = InvoiceLayout::where('business_id', $business_id)->find($id);
            if (empty($layout)) {
                throw new \Exception('Layout not found');
            }

            $input = $this->layoutFormInput($request, true);
            $this->applyUploadedFiles($request, $input);

            if (! empty($request->input('is_default'))) {
                InvoiceLayout::where('business_id', $business_id)
                                ->where('is_default', 1)
                                ->update(['is_default' => 0]);
                $input['is_default'] = 1;
            }

            InvoiceLayout::where('id', $id)
                        ->where('business_id', $business_id)
                        ->update($input);
            $output = ['success' => 1,
                'msg' => __('invoice.layout_updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return redirect('invoice-schemes')->with('status', $output);
    }

    /**
     * Live preview using the same receipt renderer. Does not save the layout.
     */
    public function preview(Request $request, $id)
    {
        if (! auth()->user()->can('invoice_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $stored = InvoiceLayout::where('business_id', $business_id)->findOrFail($id);

            // In-memory copy only — never persisted. Same renderer as printed invoices.
            $layout = $stored->replicate();
            $layout->id = $stored->id;
            $layout->business_id = $stored->business_id;
            $layout->logo = $stored->logo;
            $layout->letter_head = $stored->letter_head;

            $this->overlayFormOnLayout($request, $layout);

            $sale = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->whereIn('status', ['final', 'draft'])
                ->orderBy('id', 'desc')
                ->first(['id', 'location_id']);

            if (empty($sale) || empty($sale->location_id)) {
                return response()->json([
                    'success' => 0,
                    'msg' => __('invoice.layout_preview_no_sale'),
                ]);
            }

            $location_details = BusinessLocation::where('business_id', $business_id)->find($sale->location_id);
            if (empty($location_details)) {
                return response()->json([
                    'success' => 0,
                    'msg' => __('messages.something_went_wrong'),
                ]);
            }

            $business_details = $this->businessUtil->getDetails($business_id);
            $receipt_details = $this->transactionUtil->getReceiptDetails(
                $sale->id,
                $sale->location_id,
                $layout,
                $business_details,
                $location_details,
                'browser'
            );

            $currency_details = [
                'symbol' => $business_details->currency_symbol,
                'thousand_separator' => $business_details->thousand_separator,
                'decimal_separator' => $business_details->decimal_separator,
            ];
            $receipt_details->currency = $currency_details;

            $design = ! empty($receipt_details->design) ? $receipt_details->design : 'classic';
            $allowed = array_keys($this->getDesigns());
            if (! in_array($design, $allowed, true)) {
                $design = 'classic';
            }

            $html = view('sale_pos.receipts.'.$design, compact('receipt_details'))->render();

            return response()->json([
                'success' => 1,
                'html' => $html,
                'design' => $design,
                'invoice_no' => $receipt_details->invoice_no ?? '',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return response()->json([
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    private function layoutFileRules()
    {
        return [
            'name' => 'required|string|max:191',
            'design' => 'required|in:classic,elegant,detailed,columnize-taxes,slim,slim2',
            'logo' => 'nullable|mimes:jpeg,jpg,png,gif|max:1024',
            'letter_head' => 'nullable|mimes:jpeg,jpg,png,gif|max:1024',
        ];
    }

    /**
     * Same writable fields as the original store/update. Never touches invoices or numbering.
     */
    private function layoutFormInput(Request $request, $json_encode_arrays = false)
    {
        $input = $request->only(['name', 'header_text',
            'invoice_no_prefix', 'invoice_heading', 'sub_total_label', 'discount_label', 'tax_label', 'total_label', 'highlight_color', 'footer_text', 'invoice_heading_not_paid', 'invoice_heading_paid', 'total_due_label', 'customer_label', 'paid_label', 'sub_heading_line1', 'sub_heading_line2',
            'sub_heading_line3', 'sub_heading_line4', 'sub_heading_line5',
            'table_product_label', 'table_qty_label', 'table_unit_price_label',
            'table_subtotal_label', 'client_id_label', 'date_label', 'quotation_heading', 'quotation_no_prefix', 'design',
            'client_tax_label', 'cat_code_label', 'cn_heading', 'cn_no_label', 'cn_amount_label',
            'sales_person_label', 'prev_bal_label', 'date_time_format', 'change_return_label', 'round_off_label', 'commission_agent_label', ]);

        $checkboxes = ['show_business_name', 'show_location_name', 'show_landmark', 'show_city', 'show_state', 'show_country', 'show_zip_code', 'show_mobile_number', 'show_alternate_number', 'show_email', 'show_tax_1', 'show_tax_2', 'show_logo', 'show_barcode', 'show_payments', 'show_customer', 'show_client_id',
            'show_brand', 'show_sku', 'show_cat_code', 'show_sale_description', 'show_sales_person',
            'show_expiry', 'show_lot', 'show_previous_bal', 'show_image', 'show_reward_point',
            'show_qr_code', 'show_commission_agent', 'show_letter_head', ];
        foreach ($checkboxes as $name) {
            $input[$name] = ! empty($request->input($name)) ? 1 : 0;
        }

        if ($request->has('module_info')) {
            $input['module_info'] = json_encode($request->input('module_info'));
        }

        if (! empty($request->input('table_tax_headings'))) {
            $input['table_tax_headings'] = json_encode($request->input('table_tax_headings'));
        }

        $product_cf = ! empty($request->input('product_custom_fields')) ? $request->input('product_custom_fields') : null;
        $contact_cf = ! empty($request->input('contact_custom_fields')) ? $request->input('contact_custom_fields') : null;
        $location_cf = ! empty($request->input('location_custom_fields')) ? $request->input('location_custom_fields') : null;
        $common_settings = ! empty($request->input('common_settings')) ? $request->input('common_settings') : null;
        $qr_code_fields = ! empty($request->input('qr_code_fields')) ? $request->input('qr_code_fields') : null;

        $input['product_custom_fields'] = $json_encode_arrays && ! empty($product_cf) ? json_encode($product_cf) : $product_cf;
        $input['contact_custom_fields'] = $json_encode_arrays && ! empty($contact_cf) ? json_encode($contact_cf) : $contact_cf;
        $input['location_custom_fields'] = $json_encode_arrays && ! empty($location_cf) ? json_encode($location_cf) : $location_cf;
        $input['common_settings'] = $json_encode_arrays && ! empty($common_settings) ? json_encode($common_settings) : $common_settings;
        $input['qr_code_fields'] = $json_encode_arrays && ! empty($qr_code_fields) ? json_encode($qr_code_fields) : $qr_code_fields;

        return $input;
    }

    private function applyUploadedFiles(Request $request, array &$input)
    {
        $logo_name = $this->commonUtil->uploadFile($request, 'logo', 'invoice_logos', 'image');
        if (! empty($logo_name)) {
            $input['logo'] = $logo_name;
        }

        $letter_head = $this->commonUtil->uploadFile($request, 'letter_head', 'invoice_logos', 'image');
        if (! empty($letter_head)) {
            $input['letter_head'] = $letter_head;
        }
    }

    /**
     * Apply posted editor values onto an in-memory layout. Not persisted.
     */
    private function overlayFormOnLayout(Request $request, InvoiceLayout $layout)
    {
        $input = $this->layoutFormInput($request, false);
        foreach ($input as $key => $value) {
            $layout->{$key} = $value;
        }

        // getReceiptDetails() json_decodes these two columns.
        if (is_array($layout->module_info)) {
            $layout->module_info = json_encode($layout->module_info);
        }
        if (is_array($layout->table_tax_headings)) {
            $layout->table_tax_headings = json_encode($layout->table_tax_headings);
        }
        if (! is_array($layout->common_settings)) {
            $layout->common_settings = [];
        }
        if (! is_array($layout->qr_code_fields)) {
            $layout->qr_code_fields = [];
        }
        if (! is_array($layout->product_custom_fields)) {
            $layout->product_custom_fields = [];
        }
        if (! is_array($layout->contact_custom_fields)) {
            $layout->contact_custom_fields = [];
        }
        if (! is_array($layout->location_custom_fields)) {
            $layout->location_custom_fields = [];
        }
    }

    private function getDesigns()
    {
        return ['classic' => __('lang_v1.classic').' ('.__('lang_v1.for_normal_printer').')',
            'elegant' => __('lang_v1.elegant').' ('.__('lang_v1.for_normal_printer').')',
            'detailed' => __('lang_v1.detailed').' ('.__('lang_v1.for_normal_printer').')',
            'columnize-taxes' => __('lang_v1.columnize_taxes').' ('.__('lang_v1.for_normal_printer').')',
            'slim' => __('lang_v1.slim').' ('.__('lang_v1.recomended_for_80mm').')',
            'slim2' => __('lang_v1.slim').' 2 ('.__('lang_v1.recomended_for_58mm').')',
        ];
    }
}
