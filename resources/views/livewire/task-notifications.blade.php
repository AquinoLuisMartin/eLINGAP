<div class="task-notifications" wire:poll.60s.visible x-data>
    <button type="button" class="task-notifications-trigger" aria-label="Notifications ({{ number_format($taskCount) }})" title="{{ number_format($taskCount) }} pending {{ $taskCount === 1 ? 'task' : 'tasks' }}" aria-haspopup="dialog" aria-controls="task-notifications-dialog"
        @click="$refs.tasksDialog.showModal(); $wire.$refresh()">
        <span aria-hidden="true"><x-admin.icon name="bell" size="20" /></span>
        @if ($taskCount > 0)
            <span class="task-notifications-count" aria-hidden="true">{{ $taskCount > 99 ? '99+' : $taskCount }}</span>
        @endif
    </button>

    <dialog id="task-notifications-dialog" class="task-notifications-dialog" x-ref="tasksDialog" wire:ignore.self aria-labelledby="task-notifications-title" aria-describedby="task-notifications-description"
        @click="if ($event.target === $el) $el.close()">
        <div class="task-notifications-heading">
            <div>
                <h2 id="task-notifications-title">Notifications</h2>
                <p id="task-notifications-description">{{ number_format($taskCount) }} pending {{ $taskCount === 1 ? 'task' : 'tasks' }} for {{ auth()->user()->isAdmin() ? 'administration' : 'OSCA staff' }}.</p>
            </div>
            <button type="button" class="task-notifications-close" @click="$refs.tasksDialog.close()" autofocus>Close</button>
        </div>

        <div class="task-notifications-list">
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
                <p class="task-notifications-empty">No tasks currently require your attention.</p>
            @endforelse
        </div>

        <div class="task-notifications-footer">
            <span>Resolved tasks leave this list automatically.</span>
            <button type="button" class="task-notifications-refresh" wire:click="$refresh" wire:loading.attr="disabled">
                <span wire:loading.remove>Refresh</span><span wire:loading>Refreshing...</span>
            </button>
        </div>
    </dialog>
</div>
