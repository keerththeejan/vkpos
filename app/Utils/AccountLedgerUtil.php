<?php

namespace App\Utils;

use App\Account;
use App\BusinessLocation;
use App\Contact;
use App\Http\Controllers\AccountController;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Auth;

class AccountLedgerUtil extends Util
{
    /**
     * Payment accounts: credit increases the balance, debit decreases it.
     * Same formula as Account::forDropdown and the account book.
     *
     * Customers are debit-normal (a sale is a debit). Suppliers are credit-normal
     * (a purchase is a credit). Those sides are the ones already used by
     * TransactionUtil::getLedgerDetails.
     */
    public function report(array $filters, $page = 1, $perPage = 25)
    {
        $resolved = $this->resolve($filters);
        if (! empty($resolved['error'])) {
            return $resolved;
        }

        $perPage = $this->perPage($perPage);
        $page = max(1, (int) $page);
        $openingSigned = (float) $resolved['opening_signed'];

        $count = (int) DB::query()->fromSub($resolved['lines'](), 'ledger_lines')->count();
        $totals = DB::query()->fromSub($resolved['lines'](), 'ledger_lines')
            ->selectRaw('COALESCE(SUM(debit_amount), 0) as debit_total, COALESCE(SUM(credit_amount), 0) as credit_total')
            ->first();

        $debitTotal = (float) $totals->debit_total;
        $creditTotal = (float) $totals->credit_total;
        $closingSigned = $openingSigned + ($creditTotal - $debitTotal);

        $offset = 0;
        $rows = collect();
        if ($perPage > 0 && $count > 0) {
            $lastPage = (int) ceil($count / $perPage);
            $page = min($page, $lastPage);
            $offset = ($page - 1) * $perPage;
        }

        $prior = 0.0;
        if ($offset > 0) {
            $slice = DB::query()->fromSub($resolved['lines'](), 'ledger_lines')
                ->select('debit_amount', 'credit_amount')
                ->orderBy('sort_date')
                ->orderBy('sort_kind')
                ->orderBy('sort_id')
                ->limit($offset);
            $prior = (float) DB::query()->fromSub($slice, 'prior_lines')
                ->selectRaw('COALESCE(SUM(credit_amount - debit_amount), 0) as moved')
                ->value('moved');
        }

        if ($count > 0) {
            $lineQuery = DB::query()->fromSub($resolved['lines'](), 'ledger_lines')
                ->orderBy('sort_date')
                ->orderBy('sort_kind')
                ->orderBy('sort_id');
            if ($perPage > 0) {
                $lineQuery->offset($offset)->limit($perPage);
            }
            $rows = $lineQuery->get();
        }

        $balance = $openingSigned + $prior;
        $mapped = [];
        foreach ($rows as $row) {
            $debit = (float) $row->debit_amount;
            $credit = (float) $row->credit_amount;
            $balance += ($credit - $debit);
            $mapped[] = $this->mapRow($row, $balance, $resolved['account']);
        }

        $from = $count === 0 ? 0 : ($perPage > 0 ? $offset + 1 : 1);
        $to = $count === 0 ? 0 : ($perPage > 0 ? min($offset + $perPage, $count) : $count);

        return [
            'account' => $resolved['meta'],
            'opening' => $openingSigned,
            'opening_display' => $this->formatSigned($openingSigned),
            'current_display' => $resolved['meta']['current_display'],
            'debit' => $debitTotal,
            'credit' => $creditTotal,
            'debit_display' => $this->formatAmount($debitTotal),
            'credit_display' => $this->formatAmount($creditTotal),
            'closing' => $closingSigned,
            'closing_display' => $this->formatSigned($closingSigned),
            'count' => $count,
            'page' => $perPage > 0 ? $page : 1,
            'per_page' => $perPage,
            'from' => $from,
            'to' => $to,
            'last_page' => $perPage > 0 ? (int) max(1, ceil($count / max($perPage, 1))) : 1,
            'filtered' => $resolved['filtered'],
            'rows' => $mapped,
            'period' => $resolved['period'],
        ];
    }

    public function dashboardSnapshot($businessId)
    {
        $user = Auth::user();
        if (empty($user) || ! $this->canOpenLedger($user)) {
            return null;
        }

        $today = Carbon::today()->toDateString();
        $paymentAccounts = (int) DB::table('accounts')->where('business_id', $businessId)->whereNull('deleted_at')->count();
        $contacts = (int) DB::table('contacts')
            ->where('business_id', $businessId)
            ->whereIn('type', ['customer', 'supplier', 'both'])
            ->whereNull('deleted_at')
            ->count();

        $posted = (int) DB::table('account_transactions as at')
            ->join('accounts as a', 'a.id', '=', 'at.account_id')
            ->where('a.business_id', $businessId)
            ->whereNull('at.deleted_at')
            ->count();

        if ($posted > 0) {
            $row = DB::table('account_transactions as at')
                ->join('accounts as a', 'a.id', '=', 'at.account_id')
                ->where('a.business_id', $businessId)
                ->whereNull('at.deleted_at')
                ->whereDate('at.operation_date', $today)
                ->selectRaw("COUNT(*) as tx, COALESCE(SUM(CASE WHEN at.type = 'debit' THEN at.amount ELSE 0 END), 0) as debit_total, COALESCE(SUM(CASE WHEN at.type = 'credit' THEN at.amount ELSE 0 END), 0) as credit_total")
                ->first();

            return [
                'accounts' => $paymentAccounts,
                'transactions' => (int) $row->tx,
                'debit' => (float) $row->debit_total,
                'credit' => (float) $row->credit_total,
                'source' => 'accounts',
            ];
        }

        $tx = DB::table('transactions as t')
            ->leftJoin('contacts as c', 'c.id', '=', 't.contact_id')
            ->where('t.business_id', $businessId)
            ->whereDate('t.transaction_date', $today)
            ->whereNotIn('t.status', ['draft', 'cancelled'])
            ->whereIn('t.type', ['sell', 'purchase', 'sell_return', 'purchase_return', 'opening_balance', 'ledger_discount'])
            ->selectRaw("
                COUNT(*) as tx,
                COALESCE(SUM(CASE
                    WHEN t.type IN ('sell', 'purchase_return') OR t.sub_type = 'purchase_discount' THEN t.final_total
                    WHEN t.type = 'opening_balance' AND (c.type IS NULL OR c.type <> 'supplier') THEN t.final_total
                    WHEN t.type = 'ledger_discount' AND c.type = 'supplier' THEN t.final_total
                    ELSE 0 END), 0) as debit_total,
                COALESCE(SUM(CASE
                    WHEN t.type IN ('purchase', 'sell_return') OR t.sub_type = 'sell_discount' THEN t.final_total
                    WHEN t.type = 'opening_balance' AND c.type = 'supplier' THEN t.final_total
                    WHEN t.type = 'ledger_discount' AND (c.type IS NULL OR c.type <> 'supplier') THEN t.final_total
                    ELSE 0 END), 0) as credit_total
            ")
            ->first();

        $pay = DB::table('transaction_payments as tp')
            ->join('contacts as c', 'c.id', '=', 'tp.payment_for')
            ->leftJoin('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->where('c.business_id', $businessId)
            ->whereDate('tp.paid_on', $today)
            ->whereNull('tp.parent_id')
            ->where(function ($query) {
                $query->whereNull('t.type')->orWhere('t.type', '!=', 'expense');
            })
            ->where(function ($query) {
                $query->whereNull('t.status')->orWhereNotIn('t.status', ['draft', 'cancelled']);
            })
            ->selectRaw('COUNT(*) as tx, '.$this->paymentDebitSumSql().' as debit_total, '.$this->paymentCreditSumSql().' as credit_total')
            ->first();

        return [
            'accounts' => $paymentAccounts + $contacts,
            'transactions' => (int) $tx->tx + (int) $pay->tx,
            'debit' => (float) $tx->debit_total + (float) $pay->debit_total,
            'credit' => (float) $tx->credit_total + (float) $pay->credit_total,
            'source' => 'contacts',
        ];
    }

    public function searchAccounts($businessId, $term, $locationId, $permittedLocations)
    {
        $term = trim((string) $term);
        $like = '%'.$term.'%';
        $results = [];
        $user = Auth::user();

        if ($user->can('account.access')) {
            $accounts = Account::leftJoin('account_types as at', 'accounts.account_type_id', '=', 'at.id')
                ->where('accounts.business_id', $businessId)
                ->whereNull('accounts.deleted_at');
            if ($term !== '') {
                $accounts->where(function ($query) use ($like) {
                    $query->where('accounts.name', 'like', $like)
                        ->orWhere('accounts.account_number', 'like', $like)
                        ->orWhere('at.name', 'like', $like);
                });
            }

            $accountIds = $this->locationAccountIds($businessId, $locationId, $permittedLocations);
            if (is_array($accountIds)) {
                $accounts->whereIn('accounts.id', $accountIds ?: [0]);
            }

            $accountRows = $accounts->orderBy('accounts.name')
                ->limit(20)
                ->get(['accounts.id', 'accounts.name', 'accounts.account_number', 'accounts.is_closed', 'at.name as group_name']);

            $children = [];
            foreach ($accountRows as $account) {
                $code = (string) ($account->account_number ?: $account->id);
                $text = $code.' — '.$account->name;
                if ($account->is_closed) {
                    $text .= ' ('.__('account.closed').')';
                }
                $children[] = [
                    'id' => 'a-'.$account->id,
                    'text' => $text,
                    'code' => $code,
                    'name' => $account->name,
                    'group' => $account->group_name ?: __('lang_v1.payment_accounts'),
                ];
            }
            if (! empty($children)) {
                $results[] = ['text' => __('account.payment_accounts'), 'children' => $children];
            }
        }

        $contactQuery = Contact::where('business_id', $businessId)
            ->whereNull('deleted_at')
            ->whereIn('type', $this->allowedContactTypes($user));

        if ($term !== '') {
            $contactQuery->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('supplier_business_name', 'like', $like)
                    ->orWhere('contact_id', 'like', $like)
                    ->orWhere('mobile', 'like', $like);
            });
        }
        $this->applyOwnContactScope($contactQuery, $user);

        $contactRows = $contactQuery->orderBy('name')->limit(30)->get(['id', 'name', 'supplier_business_name', 'contact_id', 'type']);
        $customers = [];
        $suppliers = [];
        foreach ($contactRows as $contact) {
            $name = $contact->supplier_business_name ?: $contact->name;
            $code = (string) ($contact->contact_id ?: $contact->id);
            $group = $contact->type === 'supplier' ? __('report.supplier') : __('report.customer');
            $item = [
                'id' => 'c-'.$contact->id,
                'text' => $code.' — '.$name,
                'code' => $code,
                'name' => $name,
                'group' => $group,
            ];
            if ($contact->type === 'supplier') {
                $suppliers[] = $item;
            } else {
                $customers[] = $item;
            }
        }
        if (! empty($customers)) {
            $results[] = ['text' => __('report.customer'), 'children' => $customers];
        }
        if (! empty($suppliers)) {
            $results[] = ['text' => __('report.supplier'), 'children' => $suppliers];
        }

        return $results;
    }

    public function canOpenLedger($user)
    {
        return $user->can('account.access')
            || $user->can('customer.view')
            || $user->can('customer.view_own')
            || $user->can('supplier.view')
            || $user->can('supplier.view_own');
    }

    public function formatSigned($signed)
    {
        $signed = (float) $signed;
        $amount = $this->num_f(abs($signed), true);
        if ($signed < -0.0000001) {
            return $amount.' '.__('lang_v1.dr');
        }
        if ($signed > 0.0000001) {
            return $amount.' '.__('lang_v1.cr');
        }

        return $this->num_f(0, true);
    }

    public function formatAmount($amount)
    {
        $amount = (float) $amount;
        if (abs($amount) < 0.0000001) {
            return '-';
        }

        return $this->num_f($amount, true);
    }

    private function resolve(array $filters)
    {
        $key = (string) ($filters['account_key'] ?? '');
        if (! preg_match('/^(a|c)-(\d+)$/', $key, $match)) {
            return ['error' => 'account'];
        }

        $businessId = (int) $filters['business_id'];
        $start = $filters['start'];
        $end = $filters['end'];
        $locationId = $filters['location_id'];
        $permitted = $filters['permitted_locations'];
        $voucher = $this->voucher($filters['voucher'] ?? '');
        $search = trim((string) ($filters['search'] ?? ''));
        $statusMode = $this->statusMode($filters['status'] ?? 'posted', ! empty($filters['include_cancelled']));

        if ($match[1] === 'a') {
            if (! Auth::user()->can('account.access')) {
                return ['error' => 'denied'];
            }
            $account = Account::where('business_id', $businessId)->where('id', (int) $match[2])->first();
            if (empty($account)) {
                return ['error' => 'account'];
            }
            $allowedIds = $this->locationAccountIds($businessId, $locationId, $permitted);
            if (is_array($allowedIds) && ! in_array($account->id, $allowedIds)) {
                return ['error' => 'denied'];
            }

            $opening = $this->accountSignedBefore($account->id, $businessId, $start, $statusMode);
            $current = $this->accountSignedBefore($account->id, $businessId, '2999-12-31', $statusMode);
            $group = $this->accountGroup($account);

            return [
                'account' => $account,
                'opening_signed' => $opening,
                'filtered' => $voucher !== '' || $search !== '',
                'period' => ['start' => $start, 'end' => $end],
                'meta' => [
                    'key' => 'a-'.$account->id,
                    'name' => $account->name,
                    'code' => $account->account_number ?: (string) $account->id,
                    'group' => $group,
                    'normal' => __('lang_v1.ledger_normal_account'),
                    'kind' => 'account',
                    'current_display' => $this->formatSigned($current),
                ],
                'lines' => function () use ($account, $businessId, $start, $end, $voucher, $search, $statusMode) {
                    return $this->accountLines($account->id, $businessId, $start, $end, $voucher, $search, $statusMode);
                },
            ];
        }

        $contact = Contact::where('business_id', $businessId)->where('id', (int) $match[2])->first();
        if (empty($contact) || ! $this->canSeeContact($contact)) {
            return ['error' => empty($contact) ? 'account' : 'denied'];
        }

        $side = $contact->type === 'supplier' ? 'supplier' : 'customer';
        $openingDue = $this->contactDueBefore($contact, $businessId, $start, $locationId, $permitted, $statusMode);
        $currentDue = $this->contactDueBefore($contact, $businessId, '2999-12-31', $locationId, $permitted, $statusMode);
        $openingSigned = $side === 'supplier' ? $openingDue : -1 * $openingDue;
        $currentSigned = $side === 'supplier' ? $currentDue : -1 * $currentDue;
        $name = $contact->supplier_business_name ?: $contact->name;

        return [
            'account' => $contact,
            'opening_signed' => $openingSigned,
            'filtered' => $voucher !== '' || $search !== '',
            'period' => ['start' => $start, 'end' => $end],
            'meta' => [
                'key' => 'c-'.$contact->id,
                'name' => $name,
                'code' => $contact->contact_id ?: (string) $contact->id,
                'group' => $contact->type === 'supplier' ? __('report.supplier') : ($contact->type === 'both' ? __('lang_v1.both_supplier_customer') : __('report.customer')),
                'normal' => $side === 'supplier' ? __('lang_v1.ledger_normal_supplier') : __('lang_v1.ledger_normal_customer'),
                'kind' => 'contact',
                'current_display' => $this->formatSigned($currentSigned),
            ],
            'lines' => function () use ($contact, $businessId, $start, $end, $locationId, $permitted, $voucher, $search, $statusMode, $side) {
                return $this->contactLines($contact, $businessId, $start, $end, $locationId, $permitted, $voucher, $search, $statusMode, $side);
            },
        ];
    }

    private function accountLines($accountId, $businessId, $start, $end, $voucher, $search, $statusMode)
    {
        $query = $this->accountBase($accountId, $businessId, $statusMode)
            ->whereDate('at.operation_date', '>=', $start)
            ->whereDate('at.operation_date', '<=', $end);
        $this->applyAccountVoucher($query, $voucher);
        $this->applyAccountSearch($query, $search);

        return $query->select($this->accountSelect());
    }

    private function accountSignedBefore($accountId, $businessId, $before, $statusMode)
    {
        $row = $this->accountBase($accountId, $businessId, $statusMode)
            ->where('at.operation_date', '<', $before)
            ->selectRaw("COALESCE(SUM(CASE WHEN at.type = 'credit' THEN at.amount ELSE -1 * at.amount END), 0) as signed_balance")
            ->first();

        return (float) $row->signed_balance;
    }

    private function accountBase($accountId, $businessId, $statusMode)
    {
        $query = DB::table('account_transactions as at')
            ->join('accounts as a', 'a.id', '=', 'at.account_id')
            ->leftJoin('transactions as t', 't.id', '=', 'at.transaction_id')
            ->leftJoin('transaction_payments as tp', 'tp.id', '=', 'at.transaction_payment_id')
            ->leftJoin('contacts as c', 'c.id', '=', 'tp.payment_for')
            ->leftJoin('users as u', 'u.id', '=', 'at.created_by')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id')
            ->where('a.business_id', $businessId)
            ->where('at.account_id', $accountId)
            ->whereNull('at.deleted_at');
        $this->applyStatus($query, 't.status', $statusMode, true);

        return $query;
    }

    private function accountSelect()
    {
        return [
            'at.operation_date as sort_date',
            'at.id as sort_id',
            DB::raw('0 as sort_kind'),
            'at.transaction_id',
            'at.account_id',
            DB::raw("CASE WHEN at.sub_type IS NOT NULL AND at.sub_type != '' THEN at.sub_type ELSE COALESCE(t.type, 'account') END as voucher_type"),
            DB::raw("CASE
                WHEN t.type IN ('sell', 'sell_return') AND t.invoice_no IS NOT NULL AND t.invoice_no != '' THEN t.invoice_no
                WHEN t.ref_no IS NOT NULL AND t.ref_no != '' THEN t.ref_no
                WHEN at.reff_no IS NOT NULL AND at.reff_no != '' THEN at.reff_no
                WHEN tp.payment_ref_no IS NOT NULL AND tp.payment_ref_no != '' THEN tp.payment_ref_no
                ELSE CONCAT('AT-', at.id) END as voucher_no"),
            DB::raw("COALESCE(NULLIF(t.ref_no, ''), NULLIF(tp.payment_ref_no, ''), NULLIF(at.reff_no, ''), '') as reference_no"),
            DB::raw("COALESCE(NULLIF(at.note, ''), NULLIF(t.additional_notes, ''), NULLIF(c.supplier_business_name, ''), NULLIF(c.name, ''), '') as description"),
            DB::raw("COALESCE(t.status, 'final') as status"),
            'bl.name as branch',
            DB::raw("TRIM(CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) as created_by_name"),
            'at.created_at',
            DB::raw("CASE WHEN at.type = 'debit' THEN at.amount ELSE 0 END as debit_amount"),
            DB::raw("CASE WHEN at.type = 'credit' THEN at.amount ELSE 0 END as credit_amount"),
        ];
    }

    private function contactLines($contact, $businessId, $start, $end, $locationId, $permitted, $voucher, $search, $statusMode, $side)
    {
        $transactions = $this->contactTransactionQuery($contact, $businessId, $locationId, $permitted, $statusMode, $side)
            ->whereDate('t.transaction_date', '>=', $start)
            ->whereDate('t.transaction_date', '<=', $end);
        $payments = $this->contactPaymentQuery($contact, $businessId, $locationId, $permitted, $statusMode, $side)
            ->whereDate('tp.paid_on', '>=', $start)
            ->whereDate('tp.paid_on', '<=', $end);

        if (in_array($voucher, ['sell', 'purchase', 'sell_return', 'purchase_return', 'opening_balance', 'ledger_discount', 'expense'], true)) {
            $transactions->where('t.type', $voucher);
            $payments->whereRaw('1 = 0');
        } elseif ($voucher === 'payment') {
            $transactions->whereRaw('1 = 0');
        } elseif (in_array($voucher, ['fund_transfer', 'deposit'], true)) {
            $transactions->whereRaw('1 = 0');
            $payments->whereRaw('1 = 0');
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $transactions->where(function ($query) use ($like) {
                $query->where('t.invoice_no', 'like', $like)
                    ->orWhere('t.ref_no', 'like', $like)
                    ->orWhere('t.additional_notes', 'like', $like);
            });
            $payments->where(function ($query) use ($like) {
                $query->where('tp.payment_ref_no', 'like', $like)
                    ->orWhere('tp.note', 'like', $like)
                    ->orWhere('t.invoice_no', 'like', $like)
                    ->orWhere('t.ref_no', 'like', $like)
                    ->orWhere('c.name', 'like', $like)
                    ->orWhere('c.supplier_business_name', 'like', $like);
            });
        }

        return $transactions->select($this->contactTransactionSelect($side))
            ->unionAll($payments->select($this->contactPaymentSelect($side)));
    }

    private function contactDueBefore($contact, $businessId, $before, $locationId, $permitted, $statusMode)
    {
        $tx = DB::table('transactions as t')
            ->where('t.business_id', $businessId)
            ->where('t.contact_id', $contact->id)
            ->where('t.transaction_date', '<', $before)
            ->whereIn('t.type', ['purchase', 'sell', 'sell_return', 'purchase_return', 'opening_balance', 'ledger_discount']);
        $this->applyStatus($tx, 't.status', $statusMode, false);
        $this->applyTransactionLocation($tx, 't.location_id', $locationId, $permitted);
        $sums = $tx->selectRaw("
            COALESCE(SUM(CASE WHEN t.type = 'purchase' THEN t.final_total ELSE 0 END), 0) as total_purchase,
            COALESCE(SUM(CASE WHEN t.type = 'sell' AND t.status = 'final' THEN t.final_total ELSE 0 END), 0) as total_invoice,
            COALESCE(SUM(CASE WHEN t.type = 'sell_return' THEN t.final_total ELSE 0 END), 0) as total_sell_return,
            COALESCE(SUM(CASE WHEN t.type = 'purchase_return' THEN t.final_total ELSE 0 END), 0) as total_purchase_return,
            COALESCE(SUM(CASE WHEN t.type = 'opening_balance' THEN t.final_total ELSE 0 END), 0) as total_opening_balance,
            COALESCE(SUM(CASE WHEN t.type = 'ledger_discount' THEN t.final_total ELSE 0 END), 0) as total_ledger_discount
        ")->first();

        $payments = DB::table('transaction_payments as tp')
            ->leftJoin('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->where('tp.payment_for', $contact->id)
            ->where('tp.paid_on', '<', $before)
            ->where(function ($query) {
                $query->whereNull('t.type')->orWhere('t.type', '!=', 'expense');
            });
        $this->applyStatus($payments, 't.status', $statusMode, true);
        $this->applyPaymentLocation($payments, $locationId, $permitted);
        $paid = $payments->selectRaw("
            COALESCE(SUM(CASE WHEN t.type = 'sell' AND COALESCE(tp.is_return, 0) = 0 THEN tp.amount ELSE 0 END), 0) as invoice_paid,
            COALESCE(SUM(CASE WHEN t.type = 'sell' AND COALESCE(tp.is_return, 0) = 1 THEN tp.amount ELSE 0 END), 0) as change_return,
            COALESCE(SUM(CASE WHEN t.type = 'opening_balance' AND COALESCE(tp.is_return, 0) = 0 THEN tp.amount ELSE 0 END), 0) as ob_paid,
            COALESCE(SUM(CASE WHEN t.type = 'purchase' AND COALESCE(tp.is_return, 0) = 0 THEN tp.amount ELSE 0 END), 0) as purchase_paid,
            COALESCE(SUM(CASE WHEN t.type = 'sell_return' THEN tp.amount ELSE 0 END), 0) as sell_return_paid,
            COALESCE(SUM(CASE WHEN t.type = 'purchase_return' THEN tp.amount ELSE 0 END), 0) as purchase_return_paid
        ")->first();

        $advance = DB::table('transaction_payments as tp')
            ->leftJoin('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->where('tp.payment_for', $contact->id)
            ->where('tp.is_advance', 1)
            ->where('tp.paid_on', '<', $before)
            ->where(function ($query) {
                $query->whereNull('t.type')->orWhere('t.type', '!=', 'expense');
            });
        $this->applyPaymentLocation($advance, $locationId, $permitted);
        $advanceAmount = (float) $advance->selectRaw('COALESCE(SUM(tp.amount - COALESCE((SELECT SUM(child.amount) FROM transaction_payments as child WHERE child.parent_id = tp.id), 0)), 0) as amt')->value('amt');

        $invoice = (float) $sums->total_purchase + (float) $sums->total_invoice - (float) $sums->total_sell_return - (float) $sums->total_purchase_return + (float) $sums->total_opening_balance - (float) $sums->total_ledger_discount;
        $paidTotal = (float) $paid->invoice_paid - (float) $paid->change_return + (float) $paid->purchase_paid - (float) $paid->sell_return_paid - (float) $paid->purchase_return_paid + (float) $paid->ob_paid + $advanceAmount;

        return $invoice - $paidTotal;
    }

    private function contactTransactionQuery($contact, $businessId, $locationId, $permitted, $statusMode, $side)
    {
        $query = DB::table('transactions as t')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id')
            ->leftJoin('users as u', 'u.id', '=', 't.created_by')
            ->where('t.business_id', $businessId)
            ->where('t.contact_id', $contact->id)
            ->whereIn('t.type', ['sell', 'purchase', 'sell_return', 'purchase_return', 'opening_balance', 'ledger_discount']);
        $this->applyStatus($query, 't.status', $statusMode, false);
        $this->applyTransactionLocation($query, 't.location_id', $locationId, $permitted);

        return $query;
    }

    private function contactPaymentQuery($contact, $businessId, $locationId, $permitted, $statusMode, $side)
    {
        $query = DB::table('transaction_payments as tp')
            ->leftJoin('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->leftJoin('contacts as c', 'c.id', '=', 'tp.payment_for')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id')
            ->leftJoin('users as u', 'u.id', '=', 'tp.created_by')
            ->where('tp.payment_for', $contact->id)
            ->whereNull('tp.parent_id')
            ->where(function ($inner) {
                $inner->whereNull('t.type')->orWhere('t.type', '!=', 'expense');
            });
        $this->applyStatus($query, 't.status', $statusMode, true);
        $this->applyPaymentLocation($query, $locationId, $permitted);

        return $query;
    }

    private function contactTransactionSelect($side)
    {
        $supplier = $side === 'supplier' ? '1' : '0';

        return [
            't.transaction_date as sort_date',
            't.id as sort_id',
            DB::raw('0 as sort_kind'),
            't.id as transaction_id',
            DB::raw('NULL as account_id'),
            't.type as voucher_type',
            DB::raw("CASE WHEN t.type IN ('sell', 'sell_return') THEN t.invoice_no ELSE t.ref_no END as voucher_no"),
            DB::raw("COALESCE(t.ref_no, '') as reference_no"),
            DB::raw("COALESCE(t.additional_notes, '') as description"),
            't.status as status',
            'bl.name as branch',
            DB::raw("TRIM(CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) as created_by_name"),
            't.created_at',
            DB::raw("CASE
                WHEN t.type IN ('sell', 'purchase_return') OR t.sub_type = 'purchase_discount' THEN t.final_total
                WHEN t.type = 'opening_balance' AND {$supplier} = 0 THEN t.final_total
                WHEN t.type = 'ledger_discount' AND {$supplier} = 1 THEN t.final_total
                ELSE 0 END as debit_amount"),
            DB::raw("CASE
                WHEN t.type IN ('purchase', 'sell_return') OR t.sub_type = 'sell_discount' THEN t.final_total
                WHEN t.type = 'opening_balance' AND {$supplier} = 1 THEN t.final_total
                WHEN t.type = 'ledger_discount' AND {$supplier} = 0 THEN t.final_total
                ELSE 0 END as credit_amount"),
        ];
    }

    private function contactPaymentSelect($side)
    {
        return [
            'tp.paid_on as sort_date',
            'tp.id as sort_id',
            DB::raw('1 as sort_kind'),
            'tp.transaction_id',
            DB::raw('NULL as account_id'),
            DB::raw("'payment' as voucher_type"),
            DB::raw("COALESCE(NULLIF(tp.payment_ref_no, ''), CONCAT('PAY-', tp.id)) as voucher_no"),
            DB::raw("CASE WHEN t.type IN ('sell', 'sell_return') THEN COALESCE(t.invoice_no, '') ELSE COALESCE(t.ref_no, '') END as reference_no"),
            DB::raw("COALESCE(tp.note, '') as description"),
            DB::raw("COALESCE(t.status, 'final') as status"),
            'bl.name as branch',
            DB::raw("TRIM(CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) as created_by_name"),
            'tp.created_at',
            DB::raw('CASE WHEN tp.method = \'advance\' THEN 0 ELSE '.$this->paymentDebitCase($side).' END as debit_amount'),
            DB::raw('CASE WHEN tp.method = \'advance\' THEN 0 ELSE '.$this->paymentCreditCase($side).' END as credit_amount'),
        ];
    }

    private function paymentDebitCase($side)
    {
        $supplierAdvance = $side === 'supplier' ? 'tp.is_advance = 1' : '0';

        return "(CASE WHEN (
            t.type IN ('purchase', 'sell_return')
            OR ({$supplierAdvance})
            OR (t.type IN ('sell', 'purchase_return', 'opening_balance') AND COALESCE(tp.is_return, 0) = 1)
            OR tp.payment_type = 'debit'
        ) THEN tp.amount ELSE 0 END)";
    }

    private function paymentCreditCase($side)
    {
        $customerAdvance = $side !== 'supplier' ? 'tp.is_advance = 1' : '0';

        return "(CASE WHEN (
            ((t.type IN ('sell', 'purchase_return', 'opening_balance') OR ({$customerAdvance})) AND COALESCE(tp.is_return, 0) = 0)
            OR tp.payment_type = 'credit'
        ) THEN tp.amount ELSE 0 END)";
    }

    private function paymentDebitSumSql()
    {
        return "COALESCE(SUM(CASE WHEN tp.method = 'advance' THEN 0 WHEN c.type = 'supplier' AND (
            t.type IN ('purchase', 'sell_return') OR tp.is_advance = 1 OR (t.type IN ('sell', 'purchase_return', 'opening_balance') AND COALESCE(tp.is_return, 0) = 1) OR tp.payment_type = 'debit'
        ) THEN tp.amount WHEN (c.type IS NULL OR c.type <> 'supplier') AND (
            t.type IN ('purchase', 'sell_return') OR (t.type IN ('sell', 'purchase_return', 'opening_balance') AND COALESCE(tp.is_return, 0) = 1) OR tp.payment_type = 'debit'
        ) THEN tp.amount ELSE 0 END), 0)";
    }

    private function paymentCreditSumSql()
    {
        return "COALESCE(SUM(CASE WHEN tp.method = 'advance' THEN 0 WHEN c.type = 'supplier' AND (
            (t.type IN ('sell', 'purchase_return', 'opening_balance') AND COALESCE(tp.is_return, 0) = 0) OR tp.payment_type = 'credit'
        ) THEN tp.amount WHEN (c.type IS NULL OR c.type <> 'supplier') AND (
            ((t.type IN ('sell', 'purchase_return', 'opening_balance') OR tp.is_advance = 1) AND COALESCE(tp.is_return, 0) = 0) OR tp.payment_type = 'credit'
        ) THEN tp.amount ELSE 0 END), 0)";
    }

    private function mapRow($row, $balance, $party)
    {
        $type = (string) $row->voucher_type;
        $transactionId = ! empty($row->transaction_id) ? (int) $row->transaction_id : null;
        $accountId = ! empty($row->account_id) ? (int) $row->account_id : (! empty($party->id) && $party instanceof Account ? (int) $party->id : null);

        return [
            'date' => $this->format_date($row->sort_date),
            'voucher' => $row->voucher_no ?: '-',
            'type' => $this->voucherLabel($type),
            'type_key' => $type,
            'reference' => ($row->reference_no && $row->reference_no !== $row->voucher_no) ? $row->reference_no : '-',
            'description' => trim(preg_replace('/\s+/', ' ', strip_tags((string) $row->description))) ?: '-',
            'debit' => $this->formatAmount($row->debit_amount),
            'credit' => $this->formatAmount($row->credit_amount),
            'balance' => $this->formatSigned($balance),
            'url' => $this->voucherUrl($type, $transactionId, $accountId),
            'status' => $this->statusLabel($row->status),
            'status_key' => (string) $row->status,
            'branch' => $row->branch ?: '-',
            'created_by' => trim((string) $row->created_by_name) ?: '-',
            'created_at' => ! empty($row->created_at) ? $this->format_date($row->created_at, true) : '-',
            'cancelled' => $row->status === 'cancelled',
        ];
    }

    private function voucherUrl($type, $transactionId, $accountId)
    {
        if (in_array($type, ['fund_transfer', 'deposit', 'opening_balance'], true) && ! empty($accountId) && empty($transactionId)) {
            return action([AccountController::class, 'show'], [$accountId]);
        }
        if (empty($transactionId)) {
            return null;
        }
        if (in_array($type, ['sell', 'sell_return'], true)) {
            return url('/sells/'.$transactionId);
        }
        if (in_array($type, ['purchase', 'purchase_return'], true)) {
            return url('/purchases/'.$transactionId);
        }
        if ($type === 'payment') {
            return null;
        }
        if (in_array($type, ['expense', 'expense_refund'], true) && Auth::user()->can('expense.access')) {
            return url('/expenses/'.$transactionId.'/edit');
        }

        return null;
    }

    private function voucherLabel($type)
    {
        $labels = [
            'sell' => __('sale.sale'),
            'purchase' => __('lang_v1.purchase'),
            'sell_return' => __('lang_v1.sell_return'),
            'purchase_return' => __('lang_v1.purchase_return'),
            'expense' => __('lang_v1.expense'),
            'expense_refund' => __('lang_v1.expense'),
            'payment' => __('lang_v1.payment'),
            'opening_balance' => __('lang_v1.opening_balance'),
            'fund_transfer' => __('account.fund_transfer'),
            'deposit' => __('account.deposit'),
            'ledger_discount' => __('lang_v1.ledger_discount'),
            'payroll' => __('lang_v1.payroll'),
        ];

        return $labels[$type] ?? ucwords(str_replace('_', ' ', $type));
    }

    private function statusLabel($status)
    {
        if ($status === 'draft') {
            return __('lang_v1.ledger_draft');
        }
        if ($status === 'cancelled') {
            return __('lang_v1.ledger_cancelled');
        }

        return __('lang_v1.ledger_posted');
    }

    private function accountGroup(Account $account)
    {
        $type = DB::table('account_types as child')
            ->leftJoin('account_types as parent', 'parent.id', '=', 'child.parent_account_type_id')
            ->where('child.id', $account->account_type_id)
            ->first(['child.name as child_name', 'parent.name as parent_name']);
        if (empty($type)) {
            return __('account.payment_accounts');
        }

        return $type->parent_name ? $type->parent_name.' / '.$type->child_name : $type->child_name;
    }

    private function applyAccountVoucher($query, $voucher)
    {
        if ($voucher === '') {
            return;
        }
        if ($voucher === 'payment') {
            $query->whereNotNull('at.transaction_payment_id')->whereNull('at.sub_type');

            return;
        }
        if (in_array($voucher, ['fund_transfer', 'deposit'], true)) {
            $query->where('at.sub_type', $voucher);

            return;
        }
        if ($voucher === 'opening_balance') {
            $query->where(function ($inner) {
                $inner->where('at.sub_type', 'opening_balance')->orWhere('t.type', 'opening_balance');
            });

            return;
        }
        $query->where('t.type', $voucher);
    }

    private function applyAccountSearch($query, $search)
    {
        if ($search === '') {
            return;
        }
        $like = '%'.$search.'%';
        $query->where(function ($inner) use ($like) {
            $inner->where('at.reff_no', 'like', $like)
                ->orWhere('at.note', 'like', $like)
                ->orWhere('t.invoice_no', 'like', $like)
                ->orWhere('t.ref_no', 'like', $like)
                ->orWhere('t.additional_notes', 'like', $like)
                ->orWhere('tp.payment_ref_no', 'like', $like)
                ->orWhere('c.name', 'like', $like)
                ->orWhere('c.supplier_business_name', 'like', $like);
        });
    }

    private function applyStatus($query, $column, $statusMode, $allowNull)
    {
        if ($statusMode === 'draft') {
            $query->where($column, 'draft');

            return;
        }
        if ($statusMode === 'all') {
            return;
        }
        if ($statusMode === 'exclude_cancelled') {
            $query->where(function ($inner) use ($column, $allowNull) {
                if ($allowNull) {
                    $inner->whereNull($column)->orWhere($column, '!=', 'cancelled');
                } else {
                    $inner->where($column, '!=', 'cancelled');
                }
            });

            return;
        }
        $excluded = $statusMode === 'posted_cancelled' ? ['draft'] : ['draft', 'cancelled'];
        $query->where(function ($inner) use ($column, $excluded, $allowNull) {
            if ($allowNull) {
                $inner->whereNull($column)->orWhereNotIn($column, $excluded);
            } else {
                $inner->whereNotIn($column, $excluded);
            }
        });
    }

    private function applyTransactionLocation($query, $column, $locationId, $permitted)
    {
        if (! empty($locationId)) {
            $query->where($column, $locationId);

            return;
        }
        if (is_array($permitted)) {
            $query->whereIn($column, $permitted ?: [0]);
        }
    }

    private function applyPaymentLocation($query, $locationId, $permitted)
    {
        if (! empty($locationId)) {
            $query->where(function ($inner) use ($locationId) {
                $inner->where('tp.is_advance', 1)->orWhere('t.location_id', $locationId);
            });

            return;
        }
        if (is_array($permitted)) {
            $query->where(function ($inner) use ($permitted) {
                $inner->where('tp.is_advance', 1)->orWhereIn('t.location_id', $permitted ?: [0]);
            });
        }
    }

    private function locationAccountIds($businessId, $locationId, $permittedLocations)
    {
        $locationQuery = BusinessLocation::where('business_id', $businessId);
        if (! empty($locationId)) {
            $locationQuery->where('id', $locationId);
        } elseif ($permittedLocations !== 'all' && is_array($permittedLocations)) {
            $locationQuery->whereIn('id', $permittedLocations);
        } else {
            return null;
        }

        $ids = [];
        foreach ($locationQuery->get(['default_payment_accounts']) as $location) {
            if (empty($location->default_payment_accounts)) {
                continue;
            }
            $accounts = json_decode($location->default_payment_accounts, true);
            if (! is_array($accounts)) {
                continue;
            }
            foreach ($accounts as $account) {
                if (! empty($account['is_enabled']) && ! empty($account['account'])) {
                    $ids[] = (int) $account['account'];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    private function allowedContactTypes($user)
    {
        $types = [];
        if ($user->can('customer.view') || $user->can('customer.view_own')) {
            $types[] = 'customer';
            $types[] = 'both';
        }
        if ($user->can('supplier.view') || $user->can('supplier.view_own')) {
            $types[] = 'supplier';
            if (! in_array('both', $types, true)) {
                $types[] = 'both';
            }
        }

        return $types ?: ['customer'];
    }

    private function canSeeContact(Contact $contact)
    {
        $user = Auth::user();
        $type = $contact->type === 'supplier' ? 'supplier' : 'customer';
        if (! $user->can($type.'.view') && ! $user->can($type.'.view_own') && ! ($contact->type === 'both' && ($user->can('supplier.view') || $user->can('customer.view') || $user->can('supplier.view_own') || $user->can('customer.view_own')))) {
            return false;
        }
        if ($user->can($type.'.view') || ($contact->type === 'both' && ($user->can('customer.view') || $user->can('supplier.view')))) {
            return true;
        }
        if ((int) $contact->created_by === (int) $user->id) {
            return true;
        }
        if (User::isSelectedContacts($user->id)) {
            return $user->contactAccess->pluck('id')->contains($contact->id);
        }

        return false;
    }

    private function applyOwnContactScope($query, $user)
    {
        $fullCustomer = $user->can('customer.view');
        $fullSupplier = $user->can('supplier.view');
        if ($fullCustomer && $fullSupplier) {
            return;
        }
        $selected = User::isSelectedContacts($user->id) ? $user->contactAccess->pluck('id')->all() : [];
        $query->where(function ($inner) use ($user, $fullCustomer, $fullSupplier, $selected) {
            if ($fullCustomer) {
                $inner->orWhereIn('type', ['customer', 'both']);
            }
            if ($fullSupplier) {
                $inner->orWhere('type', 'supplier');
            }
            $inner->orWhere('created_by', $user->id);
            if (! empty($selected)) {
                $inner->orWhereIn('id', $selected);
            }
        });
    }

    private function voucher($value)
    {
        $allowed = ['sell', 'purchase', 'sell_return', 'purchase_return', 'expense', 'payment', 'opening_balance', 'fund_transfer', 'deposit', 'ledger_discount'];

        return in_array($value, $allowed, true) ? $value : '';
    }

    private function statusMode($status, $includeCancelled)
    {
        if ($status === 'draft') {
            return 'draft';
        }
        if ($status === 'all') {
            return $includeCancelled ? 'all' : 'exclude_cancelled';
        }

        return $includeCancelled ? 'posted_cancelled' : 'posted';
    }

    private function perPage($perPage)
    {
        $perPage = (int) $perPage;
        if ($perPage === 0) {
            return 0;
        }

        return in_array($perPage, [25, 50, 100, 250], true) ? $perPage : 25;
    }
}
