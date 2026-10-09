<?php

namespace App\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\AccountType;
use App\BusinessLocation;
use App\Contact;
use App\User;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardLedgerController extends Controller
{
    protected $transactionUtil;

    protected $commonUtil;

    public function __construct(TransactionUtil $transactionUtil, Util $commonUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->commonUtil = $commonUtil;
    }

    public function options()
    {
        $businessId = $this->businessId();
        $canAccount = auth()->user()->can('account.access');
        $accounts = collect();
        $types = collect();
        if ($canAccount) {
            $accountIds = $this->resolveAccountIds($businessId, null);
            $accountQuery = Account::where('business_id', $businessId)->orderBy('name');
            if (is_array($accountIds)) {
                $accountQuery->whereIn('id', $accountIds ?: [0]);
            }
            $accounts = $accountQuery->get(['id', 'name', 'account_number', 'account_type_id', 'is_closed']);
            $types = AccountType::where('business_id', $businessId)
                ->orderBy('name')
                ->get(['id', 'name', 'parent_account_type_id']);
        }

        $locations = [];
        foreach (BusinessLocation::forDropdown($businessId, false) as $id => $name) {
            $locations[] = ['id' => $id, 'name' => $name];
        }

        return response()->json([
            'accounts' => $accounts->map(function ($account) {
                return [
                    'id' => $account->id,
                    'account_type_id' => $account->account_type_id,
                    'name' => $this->accountLabel($account->account_number, $account->name, $account->is_closed),
                ];
            })->values(),
            'account_types' => $types,
            'locations' => $locations,
            'voucher_types' => $canAccount ? $this->voucherTypes() : [],
            'currency' => session('currency.code') ?: '',
            'can' => [
                'account' => auth()->user()->can('account.access'),
                'customer' => auth()->user()->can('customer.view') || auth()->user()->can('customer.view_own'),
                'supplier' => auth()->user()->can('supplier.view') || auth()->user()->can('supplier.view_own'),
            ],
        ]);
    }

    public function contacts(Request $request)
    {
        $businessId = $this->businessId();
        $type = $request->input('type') === 'supplier' ? 'supplier' : 'customer';
        $this->assertContactPermission($type);

        $term = trim((string) $request->input('q', ''));
        $query = Contact::where('contacts.business_id', $businessId);
        if ($type === 'supplier') {
            $query->onlySuppliers();
        } else {
            $query->onlyCustomers();
        }

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('contacts.name', 'like', '%'.$term.'%')
                    ->orWhere('contacts.supplier_business_name', 'like', '%'.$term.'%')
                    ->orWhere('contacts.contact_id', 'like', '%'.$term.'%')
                    ->orWhere('contacts.mobile', 'like', '%'.$term.'%');
            });
        }

        $contacts = $query->select(
            'contacts.id',
            'contacts.name',
            'contacts.contact_id',
            'contacts.supplier_business_name',
            'contacts.mobile'
        )
            ->groupBy('contacts.id', 'contacts.name', 'contacts.contact_id', 'contacts.supplier_business_name', 'contacts.mobile')
            ->orderBy('contacts.name')
            ->limit(30)
            ->get();

        $results = [];
        foreach ($contacts as $contact) {
            $text = $contact->name;
            if (! empty($contact->supplier_business_name)) {
                $text = $contact->supplier_business_name.' - '.$text;
            }
            if (! empty($contact->contact_id)) {
                $text .= ' ('.$contact->contact_id.')';
            }
            $results[] = ['id' => $contact->id, 'text' => $text];
        }

        return response()->json(['results' => $results]);
    }

    public function show(Request $request)
    {
        $businessId = $this->businessId();
        $validated = $request->validate([
            'mode' => 'required|in:general,account,cash_bank,customer,supplier',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'account_id' => 'nullable|integer',
            'account_type_id' => 'nullable|integer',
            'location_id' => 'nullable|integer',
            'contact_id' => 'nullable|integer',
            'voucher_type' => 'nullable|string|max:50',
            'voucher_no' => 'nullable|string|max:100',
            'search' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:10|max:100',
            'sort' => 'nullable|in:date,voucher,account,description,debit,credit',
            'direction' => 'nullable|in:asc,desc',
            'export' => 'nullable|in:csv',
        ]);

        $mode = $validated['mode'];
        if (in_array($mode, ['general', 'account', 'cash_bank'], true)) {
            if (! auth()->user()->can('account.access')) {
                abort(403, 'Unauthorized action.');
            }

            return $this->accountLedger($request, $businessId, $validated);
        }

        $this->assertContactPermission($mode === 'supplier' ? 'supplier' : 'customer');

        return $this->contactLedger($request, $businessId, $validated);
    }

    protected function accountLedger(Request $request, $businessId, array $filters)
    {
        $mode = $filters['mode'];
        $accountId = ! empty($filters['account_id']) ? (int) $filters['account_id'] : null;
        $start = Carbon::parse($filters['start_date'])->toDateString();
        $end = Carbon::parse($filters['end_date'])->toDateString();

        if ($mode === 'account' && empty($accountId)) {
            $payload = $this->emptyPayload(__('home.ledger_select_account'), 'select');
            if (AccountTransaction::unpostedPaymentQuery($businessId)->limit(1)->exists()) {
                $payload['message'] = trim($payload['message'].' '.__('home.ledger_unlinked_hint'));
            }

            return $this->jsonOrCsv($request, $payload);
        }

        $accountIds = $this->resolveAccountIds($businessId, $filters['location_id'] ?? null);

        if (! empty($accountId)) {
            $account = Account::where('business_id', $businessId)->find($accountId);
            if (empty($account)) {
                abort(404, 'Account not found.');
            }
            if (is_array($accountIds) && ! in_array($accountId, $accountIds, true)) {
                abort(403, 'Unauthorized action.');
            }
        }

        $typeIds = $this->accountTypeIds($businessId, $filters['account_type_id'] ?? null);
        $locationIds = $this->locationIdsForLedger($filters['location_id'] ?? null);
        $hasPosted = $accountIds === null || count($accountIds) > 0;
        $includeUnposted = in_array($mode, ['general', 'cash_bank'], true)
            && empty($accountId)
            && $typeIds === null;
        $base = $hasPosted ? $this->postedAccountQuery($businessId, $accountIds, $accountId, $typeIds) : null;

        $openingMap = [];
        if ($hasPosted) {
            $openingQuery = (clone $base)
                ->whereDate('account_transactions.operation_date', '<', $start)
                ->select(
                    'account_transactions.account_id',
                    DB::raw("SUM(CASE WHEN account_transactions.type = 'credit' THEN account_transactions.amount ELSE -1 * account_transactions.amount END) as balance")
                )
                ->groupBy('account_transactions.account_id')
                ->get();
            foreach ($openingQuery as $openingRow) {
                $openingMap[(int) $openingRow->account_id] = round((float) $openingRow->balance, 4);
            }
        }

        $unpostedBase = null;
        if ($includeUnposted) {
            $unpostedBase = $this->unpostedBase($businessId, $locationIds);
            foreach ($this->unpostedOpeningMap($unpostedBase, $start) as $key => $balance) {
                $openingMap[$key] = $balance;
            }
        }

        $count = 0;
        if ($hasPosted) {
            $count += (clone $base)
                ->whereDate('account_transactions.operation_date', '>=', $start)
                ->whereDate('account_transactions.operation_date', '<=', $end)
                ->count('account_transactions.id');
        }
        if ($includeUnposted) {
            $count += (clone $unpostedBase)
                ->whereDate('tp.paid_on', '>=', $start)
                ->whereDate('tp.paid_on', '<=', $end)
                ->count('tp.id');
        }

        if ($count > 15000 && $request->input('export') !== 'csv') {
            return response()->json([
                'status' => 'error',
                'message' => 'This range has too many entries. Narrow the dates or select one account.',
                'rows' => [],
                'summary' => $this->blankSummary(),
            ], 422);
        }

        $movements = [];
        if ($hasPosted) {
            $entries = (clone $base)
                ->whereDate('account_transactions.operation_date', '>=', $start)
                ->whereDate('account_transactions.operation_date', '<=', $end)
                ->select([
                    'account_transactions.id',
                    'account_transactions.type',
                    'account_transactions.amount',
                    'account_transactions.operation_date',
                    'account_transactions.sub_type',
                    'account_transactions.note',
                    'account_transactions.reff_no',
                    'account_transactions.transaction_id',
                    'A.id as account_id',
                    'A.name as account_name',
                    'A.account_number',
                    'A.is_closed',
                    't.type as transaction_type',
                    't.invoice_no',
                    't.ref_no',
                    'tp.payment_ref_no',
                    'tp.is_return',
                    'c.name as contact_name',
                    'c.supplier_business_name',
                    'pc.name as payment_contact_name',
                    'pc.supplier_business_name as payment_contact_business',
                    'ta.name as transfer_account_name',
                ])
                ->orderBy('account_transactions.operation_date')
                ->orderBy('account_transactions.id')
                ->get();

            foreach ($entries as $entry) {
                $amount = round((float) $entry->amount, 4);
                $movements[] = [
                    'sort_id' => (int) $entry->id,
                    'date_raw' => (string) $entry->operation_date,
                    'date' => $this->commonUtil->format_date($entry->operation_date, true),
                    'voucher' => $this->voucherNo($entry),
                    'account' => $this->accountLabel($entry->account_number, $entry->account_name, $entry->is_closed),
                    'account_id' => (int) $entry->account_id,
                    'description' => $this->accountDescription($entry),
                    'contact' => $this->contactName($entry),
                    'debit_raw' => $entry->type === 'debit' ? $amount : 0,
                    'credit_raw' => $entry->type === 'credit' ? $amount : 0,
                    'sub_type' => $entry->sub_type,
                    'transaction_type' => $entry->transaction_type,
                    'transaction_id' => $entry->transaction_id,
                    'note' => $this->plain($entry->note),
                    'reff_no' => (string) $entry->reff_no,
                ];
            }
        }

        if ($includeUnposted) {
            $movements = array_merge($movements, $this->unpostedMovements($unpostedBase, $businessId, $start, $end));
        }

        usort($movements, function ($left, $right) {
            $cmp = strcmp((string) $left['date_raw'], (string) $right['date_raw']);
            if ($cmp !== 0) {
                return $cmp;
            }

            return $left['sort_id'] <=> $right['sort_id'];
        });

        $running = $openingMap;
        $built = [];
        foreach ($movements as $movement) {
            $id = $movement['account_id'];
            $current = $running[$id] ?? 0;
            $current = round($current + $movement['credit_raw'] - $movement['debit_raw'], 4);
            $running[$id] = $current;
            $movement['balance_raw'] = $current;
            $built[] = $movement;
        }

        $voucherType = $filters['voucher_type'] ?? null;
        $voucherNo = trim((string) ($filters['voucher_no'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));
        $filteredMovements = $voucherType || $voucherNo !== '' || $search !== '';

        $visible = [];
        foreach ($built as $row) {
            if (! $this->rowMatches($row, $voucherType, $voucherNo, $search)) {
                continue;
            }
            $visible[] = $this->presentAccountRow($row);
        }

        $summary = $this->accountSummary($openingMap, $built, $visible, $running, $mode, $accountId, $start);
        if (! empty($summary['opening_row']) && ! empty($account)) {
            $summary['opening_row']['account'] = $this->accountLabel($account->account_number, $account->name, $account->is_closed);
        }
        $summary['filtered_movements'] = $filteredMovements;
        $summary['currency'] = session('currency.code') ?: '';

        $this->sortRows($visible, $filters['sort'] ?? 'date', $filters['direction'] ?? 'asc');

        if ($request->input('export') === 'csv') {
            return $this->csvResponse($visible, $this->accountHeadings(), 'ledger-'.$mode.'-'.$start.'-'.$end.'.csv');
        }

        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 25);
        $total = count($visible);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $slice = array_slice($visible, ($page - 1) * $perPage, $perPage);

        return response()->json([
            'status' => $total === 0 ? 'empty' : 'ok',
            'message' => $total === 0 ? __('home.ledger_empty') : '',
            'note' => __('home.ledger_balance_note'),
            'filter_note' => $filteredMovements ? __('home.ledger_filter_note') : '',
            'rows' => array_values($slice),
            'summary' => $summary,
            'opening_row' => ($page === 1 && ($filters['sort'] ?? 'date') === 'date' && ($filters['direction'] ?? 'asc') === 'asc')
                ? $summary['opening_row']
                : null,
            'pagination' => $this->pagination($page, $perPage, $total),
            'statement_url' => null,
        ]);
    }

    protected function contactLedger(Request $request, $businessId, array $filters)
    {
        $mode = $filters['mode'];
        $contactId = ! empty($filters['contact_id']) ? (int) $filters['contact_id'] : null;
        $start = Carbon::parse($filters['start_date'])->toDateString();
        $end = Carbon::parse($filters['end_date'])->toDateString();
        $locationId = $this->resolveContactLocation($businessId, $filters['location_id'] ?? null);

        if ($locationId === false) {
            return $this->jsonOrCsv($request, $this->emptyPayload(__('home.ledger_select_branch'), 'select'));
        }

        if (empty($contactId)) {
            return $this->jsonOrCsv($request, $this->emptyPayload(__('home.ledger_select_contact'), 'select'));
        }

        $contactQuery = Contact::where('contacts.business_id', $businessId)->where('contacts.id', $contactId);
        if ($mode === 'supplier') {
            $contactQuery->onlySuppliers();
        } else {
            $contactQuery->onlyCustomers();
        }
        $contact = $contactQuery->select('contacts.*')->first();
        if (empty($contact)) {
            abort(404, 'Contact not found.');
        }

        $ledger = $this->transactionUtil->getLedgerDetails($contactId, $start, $end, 'format_1', $locationId, false);
        $openingLabel = __('lang_v1.opening_balance');
        $search = mb_strtolower(trim((string) ($filters['search'] ?? '')));
        $rows = [];

        foreach ($ledger['ledger'] as $line) {
            $isOpening = ($line['type'] ?? '') === $openingLabel
                && empty($line['ref_no'])
                && empty($line['transaction_id']);
            if ($isOpening) {
                continue;
            }

            $debit = is_numeric($line['debit'] ?? null) ? round((float) $line['debit'], 4) : 0;
            $credit = is_numeric($line['credit'] ?? null) ? round((float) $line['credit'], 4) : 0;
            $description = $this->plain($line['others'] ?? '');
            if (! empty($line['payment_method'])) {
                $description = trim($description.' '.$line['payment_method']);
            }
            if (! empty($line['location'])) {
                $description = $description !== '' ? $line['location'].' — '.$description : $line['location'];
            }

            $voucher = (string) ($line['ref_no'] ?? '');
            $account = (string) ($line['type'] ?? '');
            if ($search !== '') {
                $haystack = mb_strtolower($voucher.' '.$account.' '.$description);
                if (mb_strpos($haystack, $search) === false) {
                    continue;
                }
            }

            $rows[] = [
                'date_raw' => (string) ($line['date'] ?? ''),
                'date' => ! empty($line['date']) ? $this->commonUtil->format_date($line['date'], true) : '',
                'voucher' => $voucher,
                'account' => $account,
                'description' => $description,
                'debit' => $debit > 0 ? $this->commonUtil->num_f($debit, true) : '',
                'credit' => $credit > 0 ? $this->commonUtil->num_f($credit, true) : '',
                'debit_raw' => $debit,
                'credit_raw' => $credit,
                'balance' => (string) ($line['balance'] ?? ''),
                'voucher_url' => $this->voucherUrl($line['transaction_id'] ?? null, $line['transaction_type'] ?? null),
            ];
        }

        $this->sortRows($rows, $filters['sort'] ?? 'date', $filters['direction'] ?? 'asc');

        $movementDebit = 0;
        $movementCredit = 0;
        foreach ($rows as $row) {
            $movementDebit = round($movementDebit + $row['debit_raw'], 4);
            $movementCredit = round($movementCredit + $row['credit_raw'], 4);
        }

        $summary = $this->blankSummary();
        $summary['currency'] = session('currency.code') ?: '';
        $summary['transaction_count'] = count($rows);
        $summary['movement_debit'] = $this->commonUtil->num_f($movementDebit, true);
        $summary['movement_credit'] = $this->commonUtil->num_f($movementCredit, true);
        $summary['contact'] = [
            'name' => $contact->name,
            'code' => $contact->contact_id,
            'business_name' => $contact->supplier_business_name,
            'opening' => $this->commonUtil->num_f($ledger['beginning_balance'], true),
            'invoices' => $this->commonUtil->num_f($ledger['total_invoice'], true),
            'purchases' => $this->commonUtil->num_f($ledger['total_purchase'], true),
            'paid' => $this->commonUtil->num_f($ledger['total_paid'], true),
            'discount' => $this->commonUtil->num_f($ledger['ledger_discount'], true),
            'outstanding' => $this->commonUtil->num_f($ledger['balance_due'], true),
        ];

        $statement = action([\App\Http\Controllers\ContactController::class, 'getLedger']).'?'.http_build_query(array_filter([
            'contact_id' => $contactId,
            'start_date' => $start,
            'end_date' => $end,
            'location_id' => $locationId,
            'action' => 'pdf',
        ]));

        if ($request->input('export') === 'csv') {
            return $this->csvResponse($rows, $this->contactHeadings(), 'statement-'.$contactId.'-'.$start.'-'.$end.'.csv');
        }

        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 25);
        $total = count($rows);
        $lastPage = max(1, (int) ceil(($total ?: 1) / $perPage));
        $page = min(max(1, $page), $total === 0 ? 1 : $lastPage);

        return response()->json([
            'status' => $total === 0 ? 'empty' : 'ok',
            'message' => $total === 0 ? __('home.ledger_empty') : '',
            'note' => __('home.ledger_contact_note'),
            'filter_note' => '',
            'rows' => array_values(array_slice($rows, ($page - 1) * $perPage, $perPage)),
            'summary' => $summary,
            'opening_row' => null,
            'pagination' => $this->pagination($page, $perPage, $total),
            'statement_url' => $statement,
        ]);
    }

    protected function locationIdsForLedger($locationId)
    {
        $permitted = auth()->user()->permitted_locations();
        $locationIds = null;

        if ($permitted !== 'all') {
            $locationIds = is_array($permitted) ? array_values(array_map('intval', $permitted)) : [];
        }

        if (! empty($locationId)) {
            $locationIds = [(int) $locationId];
        }

        return $locationIds;
    }

    protected function unpostedBase($businessId, $locationIds)
    {
        $query = AccountTransaction::unpostedPaymentQuery($businessId);
        if (is_array($locationIds)) {
            $query->whereIn('t.location_id', $locationIds ?: [0]);
        }

        return $query;
    }

    protected function unpostedOpeningMap($query, $start)
    {
        $rows = (clone $query)
            ->whereDate('tp.paid_on', '<', $start)
            ->groupBy('tp.method')
            ->select([
                'tp.method',
                DB::raw('SUM('.AccountTransaction::signedAmountSql().') as balance'),
            ])
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $balance = round((float) $row->balance, 4);
            if ($balance == 0.0) {
                continue;
            }
            $map[$this->methodKey($row->method)] = $balance;
        }

        return $map;
    }

    protected function unpostedMovements($query, $businessId, $start, $end)
    {
        $entries = (clone $query)
            ->whereDate('tp.paid_on', '>=', $start)
            ->whereDate('tp.paid_on', '<=', $end)
            ->select([
                'tp.id',
                'tp.amount',
                'tp.method',
                'tp.paid_on',
                'tp.is_return',
                'tp.payment_type',
                'tp.payment_ref_no',
                'tp.note',
                'tp.transaction_id',
                't.type as transaction_type',
                't.invoice_no',
                't.ref_no',
                'c.name as contact_name',
                'c.supplier_business_name',
                'c.type as contact_type',
            ])
            ->orderBy('tp.paid_on')
            ->orderBy('tp.id')
            ->get();

        $labels = $this->commonUtil->payment_types(null, false, $businessId);
        $rows = [];
        foreach ($entries as $entry) {
            $direction = AccountTransaction::bookDirection(
                $entry->transaction_type,
                $entry->is_return,
                $entry->payment_type,
                $entry->contact_type
            );
            if ($direction === null) {
                continue;
            }

            $amount = round((float) $entry->amount, 4);
            $methodLabel = $labels[$entry->method] ?? ($entry->method ?: __('account.account'));
            $contact = $entry->supplier_business_name ?: '';
            if (! empty($entry->contact_name)) {
                $contact = $contact !== '' ? $contact.', '.$entry->contact_name : $entry->contact_name;
            }

            $voucher = (string) $entry->payment_ref_no;
            if ($voucher === '') {
                $voucher = in_array($entry->transaction_type, ['sell', 'sell_return'], true)
                    ? (string) $entry->invoice_no
                    : (string) $entry->ref_no;
            }

            $parts = [$methodLabel];
            if ($contact !== '') {
                $parts[] = $contact;
            }
            if (! empty($entry->is_return)) {
                $parts[] = __('lang_v1.change_return');
            }
            $note = $this->plain($entry->note);
            if ($note !== '') {
                $parts[] = $note;
            }

            $rows[] = [
                'sort_id' => 1000000000 + (int) $entry->id,
                'date_raw' => (string) $entry->paid_on,
                'date' => $this->commonUtil->format_date($entry->paid_on, true),
                'voucher' => $voucher,
                'account' => __('home.ledger_unlinked_method', ['method' => $methodLabel]),
                'account_id' => $this->methodKey($entry->method),
                'description' => implode(' — ', $parts),
                'contact' => $contact,
                'debit_raw' => $direction === 'debit' ? $amount : 0,
                'credit_raw' => $direction === 'credit' ? $amount : 0,
                'sub_type' => null,
                'transaction_type' => $entry->transaction_type,
                'transaction_id' => $entry->transaction_id,
                'note' => $note,
                'reff_no' => (string) $entry->payment_ref_no,
            ];
        }

        return $rows;
    }

    protected function methodKey($method)
    {
        $order = [
            'cash', 'card', 'cheque', 'bank_transfer', 'other',
            'custom_pay_1', 'custom_pay_2', 'custom_pay_3', 'custom_pay_4',
            'custom_pay_5', 'custom_pay_6', 'custom_pay_7',
        ];
        $index = array_search((string) $method, $order, true);
        if ($index === false) {
            return -1000 - (abs(crc32((string) $method)) % 100000);
        }

        return -1 - $index;
    }

    protected function postedAccountQuery($businessId, $accountIds, $accountId, $typeIds)
    {
        $query = AccountTransaction::query()
            ->join('accounts as A', 'account_transactions.account_id', '=', 'A.id')
            ->leftJoin('transactions as t', 'account_transactions.transaction_id', '=', 't.id')
            ->leftJoin('transaction_payments as tp', 'account_transactions.transaction_payment_id', '=', 'tp.id')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->leftJoin('contacts as pc', 'tp.payment_for', '=', 'pc.id')
            ->leftJoin('account_transactions as tat', 'account_transactions.transfer_transaction_id', '=', 'tat.id')
            ->leftJoin('accounts as ta', 'tat.account_id', '=', 'ta.id')
            ->where('A.business_id', $businessId)
            ->whereNull('A.deleted_at')
            ->where(function ($q) {
                $q->whereNull('account_transactions.transaction_id')
                    ->orWhere(function ($posted) {
                        $posted->whereNotNull('t.id')
                            ->where('t.status', '!=', 'draft');
                    });
            });

        if (is_array($accountIds)) {
            $query->whereIn('A.id', $accountIds ?: [0]);
        }
        if (! empty($accountId)) {
            $query->where('A.id', $accountId);
        }
        if (is_array($typeIds)) {
            $query->whereIn('A.account_type_id', $typeIds ?: [0]);
        }

        return $query;
    }

    protected function accountSummary($openingRows, array $allRows, array $visible, array $closingByAccount, $mode, $accountId, $start)
    {
        $openingDebit = 0;
        $openingCredit = 0;
        foreach ($openingRows as $balance) {
            $balance = round((float) $balance, 4);
            if ($balance >= 0) {
                $openingCredit = round($openingCredit + $balance, 4);
            } else {
                $openingDebit = round($openingDebit + abs($balance), 4);
            }
        }

        $closingDebit = 0;
        $closingCredit = 0;
        foreach ($closingByAccount as $balance) {
            $balance = round((float) $balance, 4);
            if ($balance >= 0) {
                $closingCredit = round($closingCredit + $balance, 4);
            } else {
                $closingDebit = round($closingDebit + abs($balance), 4);
            }
        }

        $movementDebit = 0;
        $movementCredit = 0;
        foreach ($visible as $row) {
            $movementDebit = round($movementDebit + $row['debit_raw'], 4);
            $movementCredit = round($movementCredit + $row['credit_raw'], 4);
        }

        $summary = $this->blankSummary();
        $summary['opening_debit'] = $this->commonUtil->num_f($openingDebit, true);
        $summary['opening_credit'] = $this->commonUtil->num_f($openingCredit, true);
        $summary['movement_debit'] = $this->commonUtil->num_f($movementDebit, true);
        $summary['movement_credit'] = $this->commonUtil->num_f($movementCredit, true);
        $summary['closing_debit'] = $this->commonUtil->num_f($closingDebit, true);
        $summary['closing_credit'] = $this->commonUtil->num_f($closingCredit, true);
        $summary['transaction_count'] = count($visible);

        if ($mode === 'cash_bank') {
            $summary['accounts'] = $this->cashAccounts($allRows, $openingRows);
        }

        $summary['opening_row'] = null;
        if (! empty($accountId)) {
            $opening = round((float) ($openingRows[$accountId] ?? 0), 4);
            $summary['opening_row'] = [
                'date' => $this->commonUtil->format_date($start, false),
                'voucher' => '',
                'account' => '',
                'description' => __('lang_v1.opening_balance'),
                'debit' => '',
                'credit' => '',
                'balance' => $this->sided($opening),
                'voucher_url' => null,
                'is_opening' => true,
            ];
        }

        return $summary;
    }

    protected function cashAccounts(array $allRows, $openingRows)
    {
        $bucket = [];
        foreach ($openingRows as $id => $balance) {
            $bucket[(int) $id] = $this->emptyCashBucket(round((float) $balance, 4));
        }
        foreach ($allRows as $row) {
            $id = (int) $row['account_id'];
            if (! isset($bucket[$id])) {
                $bucket[$id] = $this->emptyCashBucket(0);
            }
            $bucket[$id]['name'] = $row['account'];
            if ($row['sub_type'] === 'fund_transfer') {
                if ($row['credit_raw'] > 0) {
                    $bucket[$id]['transfer_in'] = round($bucket[$id]['transfer_in'] + $row['credit_raw'], 4);
                } else {
                    $bucket[$id]['transfer_out'] = round($bucket[$id]['transfer_out'] + $row['debit_raw'], 4);
                }
            } elseif ($row['sub_type'] === 'opening_balance') {
                $bucket[$id]['opening_in_period'] = round(
                    $bucket[$id]['opening_in_period'] + $row['credit_raw'] - $row['debit_raw'],
                    4
                );
            } elseif ($row['credit_raw'] > 0) {
                $bucket[$id]['receipts'] = round($bucket[$id]['receipts'] + $row['credit_raw'], 4);
            } else {
                $bucket[$id]['payments'] = round($bucket[$id]['payments'] + $row['debit_raw'], 4);
            }
        }

        $ids = array_keys($bucket);
        if (empty($ids)) {
            return [];
        }
        $accounts = Account::whereIn('id', $ids)->get(['id', 'name', 'account_number', 'is_closed']);
        $labels = [];
        foreach ($accounts as $account) {
            $labels[$account->id] = $this->accountLabel($account->account_number, $account->name, $account->is_closed);
        }

        $rows = [];
        foreach ($bucket as $id => $item) {
            $closing = round(
                $item['opening'] + $item['receipts'] - $item['payments'] + $item['transfer_in'] - $item['transfer_out'] + $item['opening_in_period'],
                4
            );
            $rows[] = [
                'name' => $labels[$id] ?? ($item['name'] ?: ('#'.$id)),
                'opening' => $this->sided($item['opening']),
                'receipts' => $this->commonUtil->num_f($item['receipts'], true),
                'payments' => $this->commonUtil->num_f($item['payments'], true),
                'transfer_in' => $this->commonUtil->num_f($item['transfer_in'], true),
                'transfer_out' => $this->commonUtil->num_f($item['transfer_out'], true),
                'closing' => $this->sided($closing),
            ];
        }

        usort($rows, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $rows;
    }

    protected function emptyCashBucket($opening)
    {
        return [
            'name' => '',
            'opening' => $opening,
            'receipts' => 0,
            'payments' => 0,
            'transfer_in' => 0,
            'transfer_out' => 0,
            'opening_in_period' => 0,
        ];
    }

    protected function presentAccountRow(array $row)
    {
        return [
            'date_raw' => $row['date_raw'],
            'date' => $row['date'],
            'voucher' => $row['voucher'],
            'account' => $row['account'],
            'description' => $row['description'],
            'debit' => $row['debit_raw'] > 0 ? $this->commonUtil->num_f($row['debit_raw'], true) : '',
            'credit' => $row['credit_raw'] > 0 ? $this->commonUtil->num_f($row['credit_raw'], true) : '',
            'debit_raw' => $row['debit_raw'],
            'credit_raw' => $row['credit_raw'],
            'balance' => $this->sided($row['balance_raw']),
            'voucher_url' => $this->voucherUrl($row['transaction_id'], $row['transaction_type']),
        ];
    }

    protected function rowMatches(array $row, $voucherType, $voucherNo, $search)
    {
        if (! empty($voucherType)) {
            $actual = ! empty($row['sub_type']) ? $row['sub_type'] : $row['transaction_type'];
            if ($actual !== $voucherType) {
                return false;
            }
        }

        if ($voucherNo !== '') {
            $haystack = mb_strtolower($row['voucher'].' '.$row['reff_no'].' '.$row['note']);
            if (mb_strpos($haystack, mb_strtolower($voucherNo)) === false) {
                return false;
            }
        }

        if ($search !== '') {
            $haystack = mb_strtolower($row['voucher'].' '.$row['account'].' '.$row['description'].' '.$row['contact'].' '.$row['note']);
            if (mb_strpos($haystack, mb_strtolower($search)) === false) {
                return false;
            }
        }

        return true;
    }

    protected function voucherNo($entry)
    {
        if (in_array($entry->transaction_type, ['sell', 'sell_return'], true) && ! empty($entry->invoice_no)) {
            return $entry->invoice_no;
        }
        if (! empty($entry->ref_no)) {
            return $entry->ref_no;
        }
        if (! empty($entry->payment_ref_no)) {
            return $entry->payment_ref_no;
        }
        if (! empty($entry->reff_no)) {
            return $entry->reff_no;
        }

        return '';
    }

    protected function accountDescription($entry)
    {
        $parts = [];
        if (! empty($entry->sub_type)) {
            $label = __('account.'.$entry->sub_type);
            if ($entry->sub_type === 'fund_transfer' && ! empty($entry->transfer_account_name)) {
                $direction = $entry->type === 'credit' ? __('account.from') : __('account.to');
                $label .= ' ('.$direction.': '.$entry->transfer_account_name.')';
            }
            $parts[] = $label;
        } elseif (! empty($entry->transaction_type)) {
            $parts[] = $this->transactionTypeLabel($entry->transaction_type);
        }

        $contact = $this->contactName($entry);
        if ($contact !== '') {
            $parts[] = $contact;
        }
        if (! empty($entry->is_return)) {
            $parts[] = __('lang_v1.change_return');
        }
        $note = $this->plain($entry->note);
        if ($note !== '') {
            $parts[] = $note;
        }

        return implode(' — ', $parts);
    }

    protected function contactName($entry)
    {
        $business = $entry->supplier_business_name ?: $entry->payment_contact_business;
        $name = $entry->contact_name ?: $entry->payment_contact_name;
        if (! empty($business) && ! empty($name)) {
            return $business.', '.$name;
        }

        return $business ?: ($name ?: '');
    }

    protected function transactionTypeLabel($type)
    {
        $types = [
            'sell' => __('sale.sale'),
            'purchase' => __('lang_v1.purchase'),
            'sell_return' => __('lang_v1.sell_return'),
            'purchase_return' => __('lang_v1.purchase_return'),
            'expense' => __('lang_v1.expense'),
            'opening_balance' => __('lang_v1.opening_balance'),
            'ledger_discount' => __('lang_v1.ledger_discount'),
            'payroll' => __('home.ledger_payroll'),
            'expense_refund' => __('home.ledger_expense_refund'),
        ];

        return $types[$type] ?? ucfirst(str_replace('_', ' ', (string) $type));
    }

    protected function voucherUrl($transactionId, $transactionType)
    {
        if (empty($transactionId) || empty($transactionType)) {
            return null;
        }

        $map = [
            'sell' => [\App\Http\Controllers\SellController::class, 'show'],
            'purchase' => [\App\Http\Controllers\PurchaseController::class, 'show'],
            'sell_return' => [\App\Http\Controllers\SellReturnController::class, 'show'],
            'purchase_return' => [\App\Http\Controllers\PurchaseReturnController::class, 'show'],
        ];

        if (! isset($map[$transactionType])) {
            return null;
        }

        return action($map[$transactionType], [$transactionId]);
    }

    protected function voucherTypes()
    {
        return [
            ['id' => 'sell', 'name' => __('sale.sale')],
            ['id' => 'purchase', 'name' => __('lang_v1.purchase')],
            ['id' => 'sell_return', 'name' => __('lang_v1.sell_return')],
            ['id' => 'purchase_return', 'name' => __('lang_v1.purchase_return')],
            ['id' => 'expense', 'name' => __('lang_v1.expense')],
            ['id' => 'fund_transfer', 'name' => __('account.fund_transfer')],
            ['id' => 'deposit', 'name' => __('account.deposit')],
            ['id' => 'opening_balance', 'name' => __('account.opening_balance')],
        ];
    }

    protected function resolveAccountIds($businessId, $locationId)
    {
        $permitted = auth()->user()->permitted_locations();
        $locationIds = null;

        if ($permitted !== 'all') {
            $locationIds = is_array($permitted) ? $permitted : [];
            if (count($locationIds) === 0) {
                return [];
            }
        }

        if (! empty($locationId)) {
            if (! User::can_access_this_location($locationId, $businessId)) {
                abort(403, 'Unauthorized action.');
            }
            $location = BusinessLocation::where('business_id', $businessId)->find($locationId);
            if (empty($location)) {
                abort(422, 'Invalid branch.');
            }
            $locationIds = [(int) $locationId];
        }

        if ($locationIds === null) {
            return null;
        }

        return $this->accountIdsForLocations($businessId, $locationIds);
    }

    protected function accountIdsForLocations($businessId, array $locationIds)
    {
        $locations = BusinessLocation::where('business_id', $businessId)
            ->whereIn('id', $locationIds)
            ->get();

        $ids = [];
        foreach ($locations as $location) {
            if (empty($location->default_payment_accounts)) {
                continue;
            }
            $decoded = json_decode($location->default_payment_accounts, true);
            if (! is_array($decoded)) {
                continue;
            }
            foreach ($decoded as $account) {
                if (! empty($account['is_enabled']) && ! empty($account['account'])) {
                    $ids[] = (int) $account['account'];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    protected function resolveContactLocation($businessId, $locationId)
    {
        $permitted = auth()->user()->permitted_locations();
        if (! empty($locationId)) {
            if (! User::can_access_this_location($locationId, $businessId)) {
                abort(403, 'Unauthorized action.');
            }
            $exists = BusinessLocation::where('business_id', $businessId)->where('id', $locationId)->exists();
            if (! $exists) {
                abort(422, 'Invalid branch.');
            }

            return (int) $locationId;
        }

        if ($permitted === 'all') {
            return null;
        }

        $ids = is_array($permitted) ? array_values($permitted) : [];
        if (count($ids) === 1) {
            return (int) $ids[0];
        }

        return false;
    }

    protected function accountTypeIds($businessId, $typeId)
    {
        if (empty($typeId)) {
            return null;
        }

        $type = AccountType::where('business_id', $businessId)->find($typeId);
        if (empty($type)) {
            abort(422, 'Invalid account group.');
        }

        return AccountType::where('business_id', $businessId)
            ->where(function ($q) use ($typeId) {
                $q->where('id', $typeId)->orWhere('parent_account_type_id', $typeId);
            })
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();
    }

    protected function assertContactPermission($type)
    {
        if ($type === 'supplier') {
            if (! auth()->user()->can('supplier.view') && ! auth()->user()->can('supplier.view_own')) {
                abort(403, 'Unauthorized action.');
            }

            return;
        }

        if (! auth()->user()->can('customer.view') && ! auth()->user()->can('customer.view_own')) {
            abort(403, 'Unauthorized action.');
        }
    }

    protected function sortRows(array &$rows, $sort, $direction)
    {
        $sort = $sort ?: 'date';
        $direction = $direction === 'desc' ? 'desc' : 'asc';
        $key = $sort === 'debit' || $sort === 'credit' ? $sort.'_raw' : ($sort === 'date' ? 'date_raw' : $sort);

        usort($rows, function ($a, $b) use ($key, $direction) {
            $left = $a[$key] ?? '';
            $right = $b[$key] ?? '';
            if (is_numeric($left) && is_numeric($right)) {
                $cmp = $left <=> $right;
            } else {
                $cmp = strcasecmp((string) $left, (string) $right);
            }

            return $direction === 'desc' ? -$cmp : $cmp;
        });
    }

    protected function sided($amount)
    {
        $amount = round((float) $amount, 4);
        $formatted = $this->commonUtil->num_f(abs($amount), true);
        if ($amount > 0) {
            return $formatted.' '.__('lang_v1.cr');
        }
        if ($amount < 0) {
            return $formatted.' '.__('lang_v1.dr');
        }

        return $formatted;
    }

    protected function accountLabel($number, $name, $closed = false)
    {
        $label = $name;
        if (! empty($number)) {
            $label = $number.' — '.$name;
        }
        if (! empty($closed)) {
            $label .= ' ('.__('account.closed').')';
        }

        return $label;
    }

    protected function plain($value)
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode((string) $value))));

        return mb_substr($text, 0, 500);
    }

    protected function pagination($page, $perPage, $total)
    {
        $last = max(1, (int) ceil($total / max(1, $perPage)));

        return [
            'page' => min($page, $last),
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $last,
        ];
    }

    protected function blankSummary()
    {
        $zero = $this->commonUtil->num_f(0, true);

        return [
            'opening_debit' => $zero,
            'opening_credit' => $zero,
            'movement_debit' => $zero,
            'movement_credit' => $zero,
            'closing_debit' => $zero,
            'closing_credit' => $zero,
            'transaction_count' => 0,
            'currency' => session('currency.code') ?: '',
            'accounts' => [],
            'contact' => null,
            'opening_row' => null,
            'filtered_movements' => false,
        ];
    }

    protected function emptyPayload($message, $status)
    {
        return [
            'status' => $status,
            'message' => $message,
            'note' => '',
            'filter_note' => '',
            'rows' => [],
            'summary' => $this->blankSummary(),
            'opening_row' => null,
            'pagination' => $this->pagination(1, 25, 0),
            'statement_url' => null,
        ];
    }

    protected function jsonOrCsv(Request $request, array $payload)
    {
        if ($request->input('export') === 'csv') {
            return $this->csvResponse([], $this->accountHeadings(), 'ledger.csv');
        }

        return response()->json($payload);
    }

    protected function accountHeadings()
    {
        $currency = session('currency.code') ?: '';

        return [
            __('messages.date'),
            __('purchase.ref_no'),
            __('account.account'),
            __('lang_v1.description'),
            __('account.debit').($currency ? ' ('.$currency.')' : ''),
            __('account.credit').($currency ? ' ('.$currency.')' : ''),
            __('home.ledger_running_balance'),
        ];
    }

    protected function contactHeadings()
    {
        $headings = $this->accountHeadings();
        $headings[2] = __('lang_v1.type');

        return $headings;
    }

    protected function csvResponse(array $rows, array $headings, $filename)
    {
        return response()->streamDownload(function () use ($rows, $headings) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $headings);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $this->csvCell($row['date'] ?? ''),
                    $this->csvCell($row['voucher'] ?? ''),
                    $this->csvCell($row['account'] ?? ''),
                    $this->csvCell($row['description'] ?? ''),
                    $this->csvCell($row['debit'] ?? ''),
                    $this->csvCell($row['credit'] ?? ''),
                    $this->csvCell($row['balance'] ?? ''),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function csvCell($value)
    {
        $value = (string) $value;
        if ($value !== '' && in_array(substr($value, 0, 1), ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }

    protected function businessId()
    {
        $businessId = request()->session()->get('user.business_id');
        if (empty($businessId)) {
            abort(403, 'Unauthorized action.');
        }

        return $businessId;
    }
}
