<?php

use App\Enums\RoleEnum;
use App\Models\User;
use App\Support\TeamBalance;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Teams')] class extends Component {
    /**
     * Supervisor chosen for each investigator, keyed by investigator id.
     *
     * @var array<int|string, string>
     */
    public array $selectedSupervisor = [];

    public function mount(): void
    {
        $this->authorize('manageTeams', User::class);
    }

    /**
     * Put an investigator in the chosen supervisor's team (or move them there).
     */
    public function assign(int $investigatorId): void
    {
        $this->authorize('manageTeams', User::class);

        $investigator = $this->findInvestigator($investigatorId);

        $validated = $this->validate([
            "selectedSupervisor.{$investigatorId}" => [
                'required',
                Rule::in($this->supervisors->pluck('id')->map(fn (int $id): string => (string) $id)->all()),
            ],
        ], [
            "selectedSupervisor.{$investigatorId}.required" => __('Choose a supervisor.'),
            "selectedSupervisor.{$investigatorId}.in" => __('Choose an active supervisor.'),
        ]);

        $supervisor = $this->supervisors->firstWhere('id', (int) $validated['selectedSupervisor'][$investigatorId]);

        $investigator->update(['supervisor_id' => $supervisor->id]);
        unset($this->selectedSupervisor[$investigatorId]);
        $this->forgetTeams();

        $teamSize = $supervisor->team()->active()->withRole(RoleEnum::INVESTIGATOR)->count();
        $isOverloaded = $this->balance->statusFor($teamSize) === TeamBalance::TOO_MANY;

        Flux::toast(
            variant: $isOverloaded ? 'warning' : 'success',
            text: $isOverloaded
                ? __(':investigator added. :supervisor now has :count investigators, above the fair share of :fair.', [
                    'investigator' => $investigator->full_name,
                    'supervisor' => $supervisor->full_name,
                    'count' => $teamSize,
                    'fair' => $this->balance->fairShareLabel(),
                ])
                : __(':investigator is now in :supervisor\'s team.', [
                    'investigator' => $investigator->full_name,
                    'supervisor' => $supervisor->full_name,
                ]),
        );
    }

    /**
     * Take an investigator out of their supervisor's team.
     */
    public function unassign(int $investigatorId): void
    {
        $this->authorize('manageTeams', User::class);

        $investigator = $this->findInvestigator($investigatorId);
        $investigator->update(['supervisor_id' => null]);
        $this->forgetTeams();

        Flux::toast(variant: 'success', text: __(':name no longer has a supervisor.', ['name' => $investigator->full_name]));
    }

    /**
     * Active supervisors with their active investigators.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function supervisors(): Collection
    {
        $activeInvestigators = fn ($query) => $query->active()->withRole(RoleEnum::INVESTIGATOR);

        return User::query()
            ->active()
            ->withRole(RoleEnum::SUPERVISOR)
            ->with(['department', 'team' => fn ($query) => $activeInvestigators($query)->with('department')->orderBy('first_name')->orderBy('last_name')])
            ->withCount(['team' => $activeInvestigators])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Active investigators with no supervisor, or whose supervisor was deactivated.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function unassigned(): Collection
    {
        return User::query()
            ->active()
            ->withRole(RoleEnum::INVESTIGATOR)
            ->where(fn ($query) => $query
                ->whereNull('supervisor_id')
                ->orWhereHas('supervisor', fn ($supervisor) => $supervisor->where('is_active', false)))
            ->with(['department', 'supervisor'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    #[Computed]
    public function balance(): TeamBalance
    {
        return new TeamBalance(
            investigatorCount: User::active()->withRole(RoleEnum::INVESTIGATOR)->count(),
            supervisorCount: $this->supervisors->count(),
        );
    }

    private function findInvestigator(int $investigatorId): User
    {
        return User::query()->active()->withRole(RoleEnum::INVESTIGATOR)->findOrFail($investigatorId);
    }

    private function forgetTeams(): void
    {
        unset($this->supervisors, $this->unassigned, $this->balance);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Teams') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Place each investigator in a supervisor\'s team. Teams well above or below the fair share are flagged.') }}</flux:text>
    </div>

    <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-zinc-600 dark:text-zinc-400">
        <span>{{ trans_choice(':count investigator|:count investigators', $this->balance->investigatorCount, ['count' => $this->balance->investigatorCount]) }}</span>
        <span>{{ trans_choice(':count supervisor|:count supervisors', $this->balance->supervisorCount, ['count' => $this->balance->supervisorCount]) }}</span>
        <span>{{ __('Fair share: :fair each', ['fair' => $this->balance->fairShareLabel()]) }}</span>
        <span>{{ trans_choice(':count unassigned|:count unassigned', $this->unassigned->count(), ['count' => $this->unassigned->count()]) }}</span>
    </div>

    {{-- Unassigned investigators --}}
    <flux:card>
        <flux:heading size="lg">{{ __('Investigators without a supervisor') }}</flux:heading>

        @if ($this->unassigned->isEmpty())
            <flux:text class="mt-2">{{ __('Every active investigator is in a team.') }}</flux:text>
        @elseif ($this->supervisors->isEmpty())
            <flux:text class="mt-2">{{ __('There are no active supervisors yet. Add a supervisor before building teams.') }}</flux:text>
        @else
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>{{ __('Investigator') }}</flux:table.column>
                    <flux:table.column>{{ __('Department') }}</flux:table.column>
                    <flux:table.column>{{ __('Supervisor') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->unassigned as $investigator)
                        <flux:table.row wire:key="unassigned-{{ $investigator->id }}">
                            <flux:table.cell>
                                <x-staff.user-link :user="$investigator" class="font-medium" />
                                @if ($investigator->supervisor)
                                    <div class="text-xs text-amber-600 dark:text-amber-400">
                                        {{ __(':name was deactivated', ['name' => $investigator->supervisor->full_name]) }}
                                    </div>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $investigator->department?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <form wire:submit="assign({{ $investigator->id }})" class="flex flex-wrap items-start gap-2">
                                    <div class="min-w-64">
                                        <flux:select wire:model="selectedSupervisor.{{ $investigator->id }}" size="sm" :placeholder="__('Choose a supervisor')">
                                            @foreach ($this->supervisors as $supervisor)
                                                <flux:select.option wire:key="option-{{ $investigator->id }}-{{ $supervisor->id }}" :value="$supervisor->id">
                                                    {{ $this->balance->optionLabel($supervisor) }}
                                                </flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:error name="selectedSupervisor.{{ $investigator->id }}" />
                                    </div>
                                    <flux:button type="submit" size="sm" variant="primary">{{ __('Assign') }}</flux:button>
                                </form>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>

    {{-- Supervisor teams --}}
    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($this->supervisors as $supervisor)
            @php
                $status = $this->balance->statusFor($supervisor->team_count);
                $difference = $this->balance->differenceFor($supervisor->team_count);
            @endphp

            <flux:card wire:key="supervisor-{{ $supervisor->id }}" class="flex flex-col gap-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <flux:heading size="lg" class="truncate"><x-staff.user-link :user="$supervisor" /></flux:heading>
                        <flux:text class="text-sm">
                            {{ $supervisor->department?->name ?? __('No department') }}
                            · {{ trans_choice(':count investigator|:count investigators', $supervisor->team_count, ['count' => $supervisor->team_count]) }}
                        </flux:text>
                    </div>

                    @if ($status === App\Support\TeamBalance::TOO_MANY)
                        <flux:badge color="red" size="sm">{{ __('Too many (+:count)', ['count' => $difference]) }}</flux:badge>
                    @elseif ($status === App\Support\TeamBalance::TOO_FEW)
                        <flux:badge color="amber" size="sm">{{ __('Too few (:count)', ['count' => '−'.abs($difference)]) }}</flux:badge>
                    @else
                        <flux:badge color="green" size="sm">{{ __('Balanced') }}</flux:badge>
                    @endif
                </div>

                @if ($supervisor->team->isEmpty())
                    <flux:text class="text-sm">{{ __('No investigators yet.') }}</flux:text>
                @else
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($supervisor->team as $investigator)
                            <li wire:key="member-{{ $investigator->id }}" class="flex flex-wrap items-center justify-between gap-2 py-2">
                                <div class="min-w-0">
                                    <x-staff.user-link :user="$investigator" class="text-sm font-medium" />
                                    <div class="text-xs text-zinc-500">{{ $investigator->department?->name ?? '—' }}</div>
                                </div>
                                <form wire:submit="assign({{ $investigator->id }})" class="flex items-center gap-2">
                                    @if ($this->supervisors->count() > 1)
                                        <flux:select wire:model="selectedSupervisor.{{ $investigator->id }}" size="sm" :placeholder="__('Move to…')" class="max-w-56">
                                            @foreach ($this->supervisors->where('id', '!=', $supervisor->id) as $otherSupervisor)
                                                <flux:select.option wire:key="move-{{ $investigator->id }}-{{ $otherSupervisor->id }}" :value="$otherSupervisor->id">
                                                    {{ $this->balance->optionLabel($otherSupervisor) }}
                                                </flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:button type="submit" size="sm">{{ __('Move') }}</flux:button>
                                    @endif
                                    <flux:button type="button" size="sm" variant="ghost" wire:click="unassign({{ $investigator->id }})">{{ __('Remove') }}</flux:button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </flux:card>
        @endforeach
    </div>
</div>
