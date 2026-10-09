<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountTransaction extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'operation_date' => 'datetime',
    ];

    public function media()
    {
        return $this->morphMany(\App\Media::class, 'model');
    }

    public function transaction()
    {
        return $this->belongsTo(\App\Transaction::class, 'transaction_id');
    }

    /**
     * Gives account transaction type from payment transaction type
     *
     * @param  string  $payment_transaction_type
     * @return string
     */
    public static function getAccountTransactionType($tansaction_type)
    {
        $account_transaction_types = [
            'sell' => 'credit',
            'purchase' => 'debit',
            'expense' => 'debit',
            'purchase_return' => 'credit',
            'sell_return' => 'debit',
            'payroll' => 'debit',
            'expense_refund' => 'credit',
            'hms_booking' => 'credit',
            'gym_subscription' => 'credit',
        ];

        return $account_transaction_types[$tansaction_type];
    }

    /**
     * Creates new account transaction
     *
     * @return obj
     */
    public static function createAccountTransaction($data)
    {
        $transaction_data = [
            'amount' => $data['amount'],
            'account_id' => $data['account_id'],
            'type' => $data['type'],
            'sub_type' => ! empty($data['sub_type']) ? $data['sub_type'] : null,
            'operation_date' => ! empty($data['operation_date']) ? $data['operation_date'] : \Carbon::now(),
            'created_by' => $data['created_by'],
            'transaction_id' => ! empty($data['transaction_id']) ? $data['transaction_id'] : null,
            'transaction_payment_id' => ! empty($data['transaction_payment_id']) ? $data['transaction_payment_id'] : null,
            'note' => ! empty($data['note']) ? $data['note'] : null,
            'transfer_transaction_id' => ! empty($data['transfer_transaction_id']) ? $data['transfer_transaction_id'] : null,
        ];

        $account_transaction = AccountTransaction::create($transaction_data);

        return $account_transaction;
    }

    /**
     * Updates transaction payment from transaction payment
     *
     * @param  obj  $transaction_payment
     * @param  array  $inputs
     * @param  string  $transaction_type
     * @return string
     */
    public static function updateAccountTransaction($transaction_payment, $transaction_type)
    {
        if (! empty($transaction_payment->account_id)) {
            $account_transaction = AccountTransaction::where(
                'transaction_payment_id',
                $transaction_payment->id
            )
                    ->first();
            if (! empty($account_transaction)) {
                $account_transaction->amount = $transaction_payment->amount;
                $account_transaction->account_id = $transaction_payment->account_id;
                $account_transaction->operation_date = $transaction_payment->paid_on;
                $account_transaction->save();

                return $account_transaction;
            } else {
                $accnt_trans_data = [
                    'amount' => $transaction_payment->amount,
                    'account_id' => $transaction_payment->account_id,
                    'type' => empty($transaction_type) ? $transaction_payment->payment_type : self::getAccountTransactionType($transaction_type),
                    'operation_date' => $transaction_payment->paid_on,
                    'created_by' => $transaction_payment->created_by,
                    'transaction_id' => $transaction_payment->transaction_id,
                    'transaction_payment_id' => $transaction_payment->id,
                ];

                //If change return then set type as debit
                if (!empty($transaction_payment->transaction) && $transaction_payment->transaction->type == 'sell' && $transaction_payment->is_return == 1) {
                    $accnt_trans_data['type'] = 'debit';
                }

                self::createAccountTransaction($accnt_trans_data);
            }
        }
    }

    public function transfer_transaction()
    {
        return $this->belongsTo(\App\AccountTransaction::class, 'transfer_transaction_id');
    }

    public function account()
    {
        return $this->belongsTo(\App\Account::class, 'account_id');
    }

    /**
     * Debit or credit direction used by the payment-account book.
     * A credit increases the book balance. Sell change is a debit.
     * Returns null when the payment is not a cash or bank movement.
     */
    public static function bookDirection($transactionType, $isReturn, $paymentType = null, $contactType = null)
    {
        if ($transactionType === 'sell' && (int) $isReturn === 1) {
            return 'debit';
        }

        $creditTypes = ['sell', 'purchase_return', 'expense_refund', 'hms_booking', 'gym_subscription'];
        $debitTypes = ['purchase', 'expense', 'sell_return', 'payroll'];

        if (in_array($transactionType, $creditTypes, true)) {
            return 'credit';
        }
        if (in_array($transactionType, $debitTypes, true)) {
            return 'debit';
        }
        if ($transactionType === 'opening_balance') {
            return $contactType === 'supplier' ? 'debit' : 'credit';
        }
        if ($paymentType === 'credit' || $paymentType === 'debit') {
            return $paymentType;
        }

        return null;
    }

    /**
     * Signed book amount: credit positive, debit negative.
     * Columns must use the aliases from unpostedPaymentQuery().
     */
    public static function signedAmountSql()
    {
        return "CASE
            WHEN t.type = 'sell' AND tp.is_return = 1 THEN -1 * tp.amount
            WHEN t.type IN ('sell', 'purchase_return', 'expense_refund', 'hms_booking', 'gym_subscription') THEN tp.amount
            WHEN t.type IN ('purchase', 'expense', 'sell_return', 'payroll') THEN -1 * tp.amount
            WHEN t.type = 'opening_balance' AND c.type = 'supplier' THEN -1 * tp.amount
            WHEN t.type = 'opening_balance' THEN tp.amount
            WHEN tp.payment_type = 'credit' THEN tp.amount
            WHEN tp.payment_type = 'debit' THEN -1 * tp.amount
            ELSE 0
        END";
    }

    /**
     * Receipts and payments that were never posted to a payment account.
     * Advance allocations and child payments are excluded so a receipt is counted once.
     */
    public static function unpostedPaymentQuery($businessId)
    {
        return \App\TransactionPayment::query()
            ->from('transaction_payments as tp')
            ->leftJoin('transactions as t', 'tp.transaction_id', '=', 't.id')
            ->leftJoin('contacts as c', 'tp.payment_for', '=', 'c.id')
            ->where('tp.business_id', $businessId)
            ->whereNull('tp.parent_id')
            ->where(function ($query) {
                $query->whereNull('tp.method')
                    ->orWhere('tp.method', '!=', 'advance');
            })
            ->whereNull('tp.account_id')
            ->whereNotExists(function ($query) {
                $query->select(\DB::raw(1))
                    ->from('account_transactions as atx')
                    ->whereColumn('atx.transaction_payment_id', 'tp.id')
                    ->whereNull('atx.deleted_at');
            })
            ->where(function ($query) {
                $query->whereNull('tp.transaction_id')
                    ->orWhere(function ($posted) {
                        $posted->whereNotNull('t.id')
                            ->where('t.status', '!=', 'draft');
                    });
            });
    }
}
