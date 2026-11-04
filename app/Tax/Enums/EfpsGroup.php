<?php

namespace App\Tax\Enums;

/**
 * The group an eFPS filer e-files under, by industry, as the BIR assigns it
 * on enrolment (RR 26-2002). Each group has its own day for the 1601-C.
 */
enum EfpsGroup: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';
    case E = 'E';

    public function label(): string
    {
        return "eFPS, Group {$this->value}";
    }

    /**
     * The day of the next month the group's 1601-C is e-filed by.
     */
    public function compensationDueDay(): int
    {
        return (int) config("tax.withholding.efps.compensation_due_day.{$this->value}");
    }
}
