@php($bellCount = $taskCount + (int) $birthdays->isNotEmpty())
@php($todayBirthdays = $birthdays->where('days', 0)->count())

<div class="task-notifications" wire:poll.60s.visible x-data
    @open-staff-notifications.window="$refs.tasksDialog.showModal(); $wire.$refresh()">
    <button type="button" class="task-notifications-trigger" aria-label="Notifications ({{ number_format($bellCount) }})" title="{{ number_format($bellCount) }} notifications" aria-haspopup="dialog" aria-controls="task-notifications-dialog"
        @click="$dispatch('open-staff-notifications')">
        <span aria-hidden="true"><x-admin.icon name="bell" size="20" /></span>
        @if ($bellCount > 0)
            <span class="task-notifications-count" aria-hidden="true">{{ $bellCount > 99 ? '99+' : $bellCount }}</span>
        @endif
    </button>

    <dialog id="task-notifications-dialog" class="task-notifications-dialog" x-ref="tasksDialog" wire:ignore.self aria-labelledby="task-notifications-title" aria-describedby="task-notifications-description"
        @click="if ($event.target === $el) $el.close()">
        <div class="task-notifications-heading">
            <div>
                <h2 id="task-notifications-title">Notifications</h2>
                <p id="task-notifications-description">
                    @if ($birthdays->isNotEmpty())
                        1 birthday summary &middot;
                    @endif
                    {{ number_format($taskCount) }} pending {{ $taskCount === 1 ? 'task' : 'tasks' }}
                </p>
            </div>
            <button type="button" class="task-notifications-close" @click="$refs.tasksDialog.close()" autofocus>Close</button>
        </div>

        <div class="task-notifications-list">
            @if ($birthdays->isNotEmpty())
                <section class="task-notifications-item" aria-labelledby="birthday-notifications-title" wire:key="birthday-notifications">
                    <div class="task-notifications-item-heading">
                        <h3 id="birthday-notifications-title" class="text-sm font-semibold">Birthdays</h3>
                        <span class="rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-800 tabular-nums dark:bg-blue-950 dark:text-blue-200">{{ number_format($birthdays->count()) }}</span>
                    </div>
                    <p>{{ number_format($todayBirthdays) }} {{ $todayBirthdays === 1 ? 'birthday' : 'birthdays' }} today &middot; {{ number_format($birthdays->count() - $todayBirthdays) }} upcoming this week</p>
                    <a class="text-button self-start hover:underline" href="{{ route('dashboard') }}#birthday-greetings"
                        @click="if (document.getElementById('birthday-greetings')) { $event.preventDefault(); $refs.tasksDialog.close(); $dispatch('open-birthday-greetings') }">
                        View birthday greetings <x-admin.icon name="arrow-up-right" :size="15" />
                    </a>
                </section>
            @endif
            @forelse ($tasks as $task)
                <section class="task-notifications-item" wire:key="task-{{ $task['key'] }}" aria-labelledby="task-title-{{ $task['key'] }}">
                    <div class="task-notifications-item-heading">
                        <h3 id="task-title-{{ $task['key'] }}">{{ $task['title'] }}</h3>
                        <span class="task-notifications-pending">{{ number_format($task['count']) }} pending</span>
                    </div>
                    <p>{{ $task['description'] }}</p>
                    <div class="task-notifications-actions">
                        @if ($task['next_url'])
                            <a class="task-notifications-action is-primary" href="{{ $task['next_url'] }}">Open next task<span class="sr-only">: {{ $task['title'] }}</span></a>
                        @endif
                        <a @class(['task-notifications-action', 'is-primary' => ! $task['next_url']]) href="{{ $task['url'] }}">View queue<span class="sr-only">: {{ $task['title'] }}</span></a>
                    </div>
                </section>
            @empty
                @if ($birthdays->isEmpty())
                    <p class="task-notifications-empty">No tasks currently require your attention.</p>
                @endif
            @endforelse
        </div>

        <div class="task-notifications-footer">
            <button type="button" class="task-notifications-refresh ml-auto" wire:click="$refresh" wire:loading.attr="disabled">
                <span wire:loading.remove>Refresh</span><span wire:loading>Refreshing...</span>
            </button>
        </div>
    </dialog>
</div>
