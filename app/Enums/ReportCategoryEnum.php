<?php

namespace App\Enums;

/**
 * Report categories tailored to a polytechnic environment.
 *
 * Bribery reports were merged into CORRUPTION ("Bribery & Corruption").
 */
enum ReportCategoryEnum: string
{
    case CORRUPTION = 'corruption';
    case FRAUD = 'fraud';
    case PROCUREMENT_FRAUD = 'procurement_fraud';
    case MISUSE_OF_FUNDS = 'misuse_of_funds';
    case THEFT_OF_PROPERTY = 'theft_of_property';
    case ACADEMIC_MISCONDUCT = 'academic_misconduct';
    case NEPOTISM = 'nepotism';
    case ABUSE_OF_POWER = 'abuse_of_power';
    case SEXUAL_HARASSMENT = 'sexual_harassment';
    case BULLYING_DISCRIMINATION = 'bullying_discrimination';
    case HEALTH_AND_SAFETY = 'health_and_safety';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CORRUPTION => 'Bribery & Corruption',
            self::FRAUD => 'Fraud',
            self::PROCUREMENT_FRAUD => 'Procurement Fraud',
            self::MISUSE_OF_FUNDS => 'Misuse of College Funds',
            self::THEFT_OF_PROPERTY => 'Theft of College Property',
            self::ACADEMIC_MISCONDUCT => 'Exam & Academic Misconduct',
            self::NEPOTISM => 'Nepotism & Favouritism',
            self::ABUSE_OF_POWER => 'Abuse of Power',
            self::SEXUAL_HARASSMENT => 'Sexual Harassment',
            self::BULLYING_DISCRIMINATION => 'Bullying, Discrimination & Victimisation',
            self::HEALTH_AND_SAFETY => 'Health & Safety',
            self::OTHER => 'Other',
        };
    }

    /**
     * A short plain-language hint shown under the label so reporters can
     * pick the right category.
     */
    public function description(): string
    {
        return match ($this) {
            self::CORRUPTION => 'Bribes, kickbacks or payments demanded for a service.',
            self::FRAUD => 'Forged documents, fake claims or falsified records.',
            self::PROCUREMENT_FRAUD => 'Rigged tenders, inflated prices or ghost suppliers.',
            self::MISUSE_OF_FUNDS => 'College money spent for private or unapproved purposes.',
            self::THEFT_OF_PROPERTY => 'Equipment, tools, fuel or supplies taken or misused.',
            self::ACADEMIC_MISCONDUCT => 'Leaked papers, paid-for marks, impersonation or fake certificates.',
            self::NEPOTISM => 'Jobs, places or rooms given to relatives or friends.',
            self::ABUSE_OF_POWER => 'Officials using their position for personal gain.',
            self::SEXUAL_HARASSMENT => 'Unwanted sexual conduct, including demands for sexual favours in exchange for marks.',
            self::BULLYING_DISCRIMINATION => 'Targeting, unfair treatment or punishment for speaking up.',
            self::HEALTH_AND_SAFETY => 'Unsafe workshops, labs, hostels or canteen hygiene.',
            self::OTHER => 'Any other wrongdoing not listed here.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CORRUPTION => 'banknotes',
            self::FRAUD => 'document-minus',
            self::PROCUREMENT_FRAUD => 'clipboard-document-list',
            self::MISUSE_OF_FUNDS => 'currency-dollar',
            self::THEFT_OF_PROPERTY => 'archive-box-x-mark',
            self::ACADEMIC_MISCONDUCT => 'academic-cap',
            self::NEPOTISM => 'user-group',
            self::ABUSE_OF_POWER => 'bolt',
            self::SEXUAL_HARASSMENT => 'hand-raised',
            self::BULLYING_DISCRIMINATION => 'no-symbol',
            self::HEALTH_AND_SAFETY => 'exclamation-triangle',
            self::OTHER => 'ellipsis-horizontal-circle',
        };
    }

    /**
     * Categories that start at high priority because of the risk of harm.
     */
    public function defaultPriority(): int
    {
        return match ($this) {
            self::SEXUAL_HARASSMENT, self::HEALTH_AND_SAFETY => 3,
            default => 2,
        };
    }
}
