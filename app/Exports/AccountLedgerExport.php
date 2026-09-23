<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class AccountLedgerExport implements FromArray
{
    protected $statement;

    public function __construct(array $statement)
    {
        $this->statement = $statement;
    }

    public function array(): array
    {
        $account = $this->statement['account'];
        $rows = [
            [session('business.name')],
            ['General ledger'],
            ['Account code', $account['code']],
            ['Account name', $account['name']],
            ['Account group', $account['group']],
            ['Period', $this->statement['period_label'] ?? ($this->statement['period']['start'].' to '.$this->statement['period']['end'])],
            ['Location', $this->statement['location_name'] ?? ''],
            [],
            ['Date', 'Voucher No', 'Voucher Type', 'Reference', 'Description', 'Debit', 'Credit', 'Balance'],
            ['', '', '', '', 'Opening balance', '', '', $this->statement['opening_display']],
        ];

        foreach ($this->statement['rows'] as $row) {
            $rows[] = [
                $row['date'],
                $row['voucher'],
                $row['type'],
                $row['reference'],
                $row['description'],
                $row['debit'],
                $row['credit'],
                $row['balance'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Opening balance', $this->statement['opening_display']];
        $rows[] = ['Total debit', $this->statement['debit_display']];
        $rows[] = ['Total credit', $this->statement['credit_display']];
        $rows[] = ['Closing balance', $this->statement['closing_display']];

        return $rows;
    }
}
