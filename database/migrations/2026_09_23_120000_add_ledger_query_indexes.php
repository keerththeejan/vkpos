<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $paymentIndexes = collect(DB::select('SHOW INDEX FROM transaction_payments'))->pluck('Key_name');
        if (! $paymentIndexes->contains('tp_payment_for_paid_on_index')) {
            Schema::table('transaction_payments', function (Blueprint $table) {
                $table->index(['payment_for', 'paid_on'], 'tp_payment_for_paid_on_index');
            });
        }

        $accountIndexes = collect(DB::select('SHOW INDEX FROM account_transactions'))->pluck('Key_name');
        if (! $accountIndexes->contains('at_account_operation_date_index')) {
            Schema::table('account_transactions', function (Blueprint $table) {
                $table->index(['account_id', 'operation_date'], 'at_account_operation_date_index');
            });
        }
    }

    public function down()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            $table->dropIndex('tp_payment_for_paid_on_index');
        });
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropIndex('at_account_operation_date_index');
        });
    }
};
