<?php

namespace App\Support;

use App\Models\User;

/**
 * Decides whether a supervisor's team is fairly sized compared with the rest.
 *
 * The fair share is the number of active investigators divided by the
 * number of active supervisors. A team larger than the fair share rounded up
 * has too many investigators; one smaller than it rounded down has too few.
 */
class TeamBalance
{
    public const BALANCED = 'balanced';

    public const TOO_MANY = 'too_many';

    public const TOO_FEW = 'too_few';

    public function __construct(
        public readonly int $investigatorCount,
        public readonly int $supervisorCount,
    ) {}

    /**
     * Investigators each supervisor should have, or null with no supervisors.
     */
    public function fairShare(): ?float
    {
        if ($this->supervisorCount === 0) {
            return null;
        }

        return $this->investigatorCount / $this->supervisorCount;
    }

    /**
     * The fair share for display, e.g. "4" or "4.5".
     */
    public function fairShareLabel(): string
    {
        $fairShare = $this->fairShare();

        return $fairShare === null ? '—' : rtrim(rtrim(number_format($fairShare, 1), '0'), '.');
    }

    /**
     * @return self::BALANCED|self::TOO_MANY|self::TOO_FEW
     */
    public function statusFor(int $teamSize): string
    {
        $fairShare = $this->fairShare();

        return match (true) {
            $fairShare === null => self::BALANCED,
            $teamSize > (int) ceil($fairShare) => self::TOO_MANY,
            $teamSize < (int) floor($fairShare) => self::TOO_FEW,
            default => self::BALANCED,
        };
    }

    /**
     * Dropdown text for a supervisor, e.g. "Chipo Mutasa — 4 investigators · too many".
     * Expects the supervisor to be loaded with a `team_count`.
     */
    public function optionLabel(User $supervisor): string
    {
        $teamSize = (int) $supervisor->team_count;
        $label = $supervisor->full_name.' — '.trans_choice(':count investigator|:count investigators', $teamSize, ['count' => $teamSize]);

        return match ($this->statusFor($teamSize)) {
            self::TOO_MANY => $label.' · '.__('too many'),
            self::TOO_FEW => $label.' · '.__('too few'),
            default => $label,
        };
    }

    /**
     * How far a team is outside the fair range: positive when too many,
     * negative when too few, zero when balanced.
     */
    public function differenceFor(int $teamSize): int
    {
        $fairShare = (float) $this->fairShare();

        return match ($this->statusFor($teamSize)) {
            self::TOO_MANY => $teamSize - (int) ceil($fairShare),
            self::TOO_FEW => $teamSize - (int) floor($fairShare),
            default => 0,
        };
    }
}
