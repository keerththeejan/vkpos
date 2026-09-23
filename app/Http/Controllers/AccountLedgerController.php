<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Exports\AccountLedgerExport;
use App\Utils\AccountLedgerUtil;
use Carbon\Carbon;
use Excel;
use Illuminate\Http\Request;

class AccountLedgerController extends Controller
{
    protected $ledgerUtil;

    public function __construct(AccountLedgerUtil $ledgerUtil)
    {
        $this->ledgerUtil = $ledgerUtil;
    }

    public function index()
    {
        $this->authorizeLedger();
        $businessId = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($businessId, true);
        $start = Carbon::now()->startOfMonth()->toDateString();
        $end = Carbon::now()->endOfMonth()->toDateString();

        return view('account.ledger', [
            'business_locations' => $business_locations,
            'default_start' => $this->ledgerUtil->format_date($start),
            'default_end' => $this->ledgerUtil->format_date($end),
            'voucher_types' => $this->voucherTypes($businessId),
        ]);
    }

    public function accounts(Request $request)
    {
        $this->authorizeLedger();
        $businessId = $request->session()->get('user.business_id');
        $locationId = $request->input('location_id');
        $permitted = auth()->user()->permitted_locations();
        if ($permitted !== 'all' && ! empty($locationId) && ! in_array($locationId, $permitted)) {
            abort(403, 'Unauthorized action.');
        }

        return response()->json([
            'results' => $this->ledgerUtil->searchAccounts($businessId, $request->input('q'), $locationId, $permitted),
        ]);
    }

    public function data(Request $request)
    {
        $this->authorizeLedger();

        return response()->json($this->statement($request, (int) $request->input('page', 1), (int) $request->input('per_page', 25)));
    }

    public function exportExcel(Request $request)
    {
        $this->authorizeLedger();
        $statement = $this->statement($request, 1, 0);
        if (! empty($statement['error'])) {
            abort(422, 'Select an account.');
        }
        $filename = 'ledger-'.$statement['account']['code'].'.xlsx';

        return Excel::download(new AccountLedgerExport($statement), $filename);
    }

    public function exportPdf(Request $request)
    {
        $this->authorizeLedger();
        $statement = $this->statement($request, 1, 0);
        if (! empty($statement['error'])) {
            abort(422, 'Select an account.');
        }

        $mpdf = $this->pdf();
        $mpdf->WriteHTML(view('account.ledger_print', $this->printData($statement))->render());

        return response($mpdf->Output('ledger-'.$statement['account']['code'].'.pdf', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="ledger-'.$statement['account']['code'].'.pdf"',
        ]);
    }

    public function printLedger(Request $request)
    {
        $this->authorizeLedger();
        $statement = $this->statement($request, 1, 0);
        if (! empty($statement['error'])) {
            abort(422, 'Select an account.');
        }

        return view('account.ledger_print', $this->printData($statement));
    }

    private function statement(Request $request, $page, $perPage)
    {
        $businessId = $request->session()->get('user.business_id');
        $permitted = auth()->user()->permitted_locations();
        $locationId = $request->input('location_id');
        if ($locationId === '' || $locationId === 'all') {
            $locationId = null;
        }
        if ($permitted !== 'all' && ! empty($locationId) && ! in_array($locationId, $permitted)) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $start = $this->ledgerUtil->uf_date($request->input('start_date'));
            $end = $this->ledgerUtil->uf_date($request->input('end_date'));
        } catch (\Exception $e) {
            $start = Carbon::now()->startOfMonth()->toDateString();
            $end = Carbon::now()->endOfMonth()->toDateString();
        }
        if (empty($start) || empty($end)) {
            $start = Carbon::now()->startOfMonth()->toDateString();
            $end = Carbon::now()->endOfMonth()->toDateString();
        }
        $start = Carbon::parse($start)->toDateString();
        $end = Carbon::parse($end)->toDateString();
        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        $report = $this->ledgerUtil->report([
            'business_id' => $businessId,
            'account_key' => $request->input('account_key'),
            'start' => $start,
            'end' => $end,
            'location_id' => $locationId,
            'permitted_locations' => $permitted,
            'voucher' => $request->input('voucher'),
            'search' => $request->input('search'),
            'status' => $request->input('status', 'posted'),
            'include_cancelled' => $request->boolean('include_cancelled'),
        ], $page, $perPage);

        if (! empty($report['error']) && $report['error'] === 'denied') {
            abort(403, 'Unauthorized action.');
        }

        if (empty($report['error'])) {
            $report['period_label'] = $this->ledgerUtil->format_date($start).' - '.$this->ledgerUtil->format_date($end);
            $report['location_name'] = __('report.all_locations');
            if (! empty($locationId)) {
                $locationName = BusinessLocation::where('business_id', $businessId)->where('id', $locationId)->value('name');
                if (! empty($locationName)) {
                    $report['location_name'] = $locationName;
                }
            }
        }

        return $report;
    }

    private function printData(array $statement)
    {
        return [
            'statement' => $statement,
            'business_name' => session('business.name'),
            'generated_at' => $this->ledgerUtil->format_date(Carbon::now()->toDateTimeString(), true),
            'period_label' => $statement['period_label'] ?? ($this->ledgerUtil->format_date($statement['period']['start']).' - '.$this->ledgerUtil->format_date($statement['period']['end'])),
            'location_name' => $statement['location_name'] ?? __('report.all_locations'),
        ];
    }

    private function pdf()
    {
        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => public_path('uploads/temp'),
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'autoVietnamese' => true,
            'autoArabic' => true,
            'useSubstitutions' => true,
        ]);
        $mpdf->setFooter((session('business.name') ?: '').'|{PAGENO} / {nbpg}');

        return $mpdf;
    }

    private function authorizeLedger()
    {
        if (! $this->ledgerUtil->canOpenLedger(auth()->user())) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function voucherTypes($businessId)
    {
        $labels = [
            'sell' => __('sale.sale'),
            'purchase' => __('lang_v1.purchase'),
            'sell_return' => __('lang_v1.sell_return'),
            'purchase_return' => __('lang_v1.purchase_return'),
            'expense' => __('lang_v1.expense'),
            'opening_balance' => __('lang_v1.opening_balance'),
            'ledger_discount' => __('lang_v1.ledger_discount'),
        ];
        $present = \DB::table('transactions')
            ->where('business_id', $businessId)
            ->whereIn('type', array_keys($labels))
            ->distinct()
            ->pluck('type')
            ->all();

        $types = ['' => __('lang_v1.all')];
        foreach ($labels as $key => $label) {
            if (in_array($key, $present, true)) {
                $types[$key] = $label;
            }
        }

        $hasPayments = \DB::table('transaction_payments as tp')
            ->join('contacts as c', 'c.id', '=', 'tp.payment_for')
            ->where('c.business_id', $businessId)
            ->limit(1)
            ->exists();
        if ($hasPayments) {
            $types['payment'] = __('lang_v1.payment');
        }

        $subTypes = \DB::table('account_transactions as at')
            ->join('accounts as a', 'a.id', '=', 'at.account_id')
            ->where('a.business_id', $businessId)
            ->whereNull('at.deleted_at')
            ->whereIn('at.sub_type', ['fund_transfer', 'deposit', 'opening_balance'])
            ->distinct()
            ->pluck('at.sub_type');
        if ($subTypes->contains('fund_transfer')) {
            $types['fund_transfer'] = __('account.fund_transfer');
        }
        if ($subTypes->contains('deposit')) {
            $types['deposit'] = __('account.deposit');
        }
        if ($subTypes->contains('opening_balance')) {
            $types['opening_balance'] = __('lang_v1.opening_balance');
        }

        return $types;
    }
}
