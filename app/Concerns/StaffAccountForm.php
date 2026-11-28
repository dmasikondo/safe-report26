<?php

namespace App\Concerns;

use App\Enums\RoleEnum;
use App\Models\Department;
use App\Models\User;
use App\Support\TeamBalance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

/**
 * Fields, options and validation shared by the Livewire pages that add and
 * edit staff accounts.
 */
trait StaffAccountForm
{
    use ProfileValidationRules;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $role = '';

    public string $department_id = '';

    public string $supervisor_id = '';

    /**
     * Roles the signed-in user may give to an account.
     *
     * @return array<int, RoleEnum>
     */
    #[Computed]
    public function creatableRoles(): array
    {
        return Auth::user()->creatableRoles();
    }

    /**
     * @return Collection<int, Department>
     */
    #[Computed]
    public function departments(): Collection
    {
        return Department::ordered()->get(['id', 'name']);
    }

    /**
     * Whether the chosen role needs a department (supervisors and investigators).
     */
    #[Computed]
    public function roleRequiresDepartment(): bool
    {
        return RoleEnum::tryFrom($this->role)?->requiresDepartment() ?? false;
    }

    /**
     * Active supervisors with their current team sizes, for the optional
     * supervisor choice of an investigator.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function supervisors(): Collection
    {
        return User::query()
            ->active()
            ->withRole(RoleEnum::SUPERVISOR)
            ->when($this->accountBeingEditedId(), fn ($query, int $id) => $query->whereKeyNot($id))
            ->withCount(['team' => fn ($query) => $query->active()->withRole(RoleEnum::INVESTIGATOR)])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    #[Computed]
    public function teamBalance(): TeamBalance
    {
        return new TeamBalance(
            investigatorCount: User::active()->withRole(RoleEnum::INVESTIGATOR)->count(),
            supervisorCount: $this->supervisors->count(),
        );
    }

    /**
     * The account being edited, left out of the supervisor choices.
     * Null when adding a new account.
     */
    protected function accountBeingEditedId(): ?int
    {
        return null;
    }

    /**
     * @param  array<int, RoleEnum>  $allowedRoles
     * @return array<string, mixed>
     */
    protected function staffAccountRules(array $allowedRoles): array
    {
        return [
            ...$this->profileRules($this->accountBeingEditedId()),
            'role' => ['required', Rule::enum(RoleEnum::class)->only($allowedRoles)],
            'department_id' => [
                Rule::requiredIf(fn (): bool => $this->roleRequiresDepartment),
                'nullable',
                'integer',
                Rule::exists('departments', 'id'),
            ],
            'supervisor_id' => [
                Rule::excludeIf(fn (): bool => $this->role !== RoleEnum::INVESTIGATOR->value),
                'nullable',
                Rule::in($this->supervisors->pluck('id')->map(fn (int $id): string => (string) $id)->all()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function staffAccountMessages(): array
    {
        return [
            'role.enum' => __('You are not allowed to give accounts this role.'),
            'department_id.required' => __('Choose the department this staff member works in.'),
            'department_id.exists' => __('Choose a department from the list.'),
            'supervisor_id.in' => __('Choose an active supervisor.'),
        ];
    }
}
