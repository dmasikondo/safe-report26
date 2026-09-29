<?php

namespace App\Enums;

enum ReportCategoryEnum: string
{
    case CORRUPTION        = 'corruption';
    case BRIBERY           = 'bribery';
    case FRAUD             = 'fraud';
    case ABUSE_OF_POWER    = 'abuse_of_power';
    case MISUSE_OF_FUNDS   = 'misuse_of_funds';
    case PROCUREMENT_FRAUD = 'procurement_fraud';
    case OTHER             = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CORRUPTION        => 'Corruption',
            self::BRIBERY           => 'Bribery',
            self::FRAUD             => 'Fraud',
            self::ABUSE_OF_POWER    => 'Abuse of Power',
            self::MISUSE_OF_FUNDS   => 'Misuse of Public Funds',
            self::PROCUREMENT_FRAUD => 'Procurement Fraud',
            self::OTHER             => 'Other',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CORRUPTION        => 'shield-x',
            self::BRIBERY           => 'banknotes',
            self::FRAUD             => 'document-minus',
            self::ABUSE_OF_POWER    => 'bolt',
            self::MISUSE_OF_FUNDS   => 'currency-dollar',
            self::PROCUREMENT_FRAUD => 'clipboard-document-list',
            self::OTHER             => 'ellipsis-horizontal-circle',
        };
    }
}
