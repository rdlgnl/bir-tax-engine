<?php

namespace App\Transactions\Enums;

enum TransactionType: string
{
    case Payment = 'payment';
    case Invoice = 'invoice';
    case Expense = 'expense';

    public function isIncome(): bool
    {
        return $this !== self::Expense;
    }
}
