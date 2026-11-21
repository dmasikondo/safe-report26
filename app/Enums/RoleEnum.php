<?php

namespace App\Enums;

enum RoleEnum: string
{
    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case SUPERVISOR = 'supervisor';
    case INVESTIGATOR = 'investigator';

    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Super Administrator',
            self::ADMIN => 'Administrator',
            self::SUPERVISOR => 'Supervisor',
            self::INVESTIGATOR => 'Investigator',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Full system access including audit log review and user management.',
            self::ADMIN => 'Manages staff accounts and system configuration.',
            self::SUPERVISOR => 'Oversees investigators; can escalate or reassign cases.',
            self::INVESTIGATOR => 'Receives, reviews, and manages assigned cases.',
        };
    }
}
