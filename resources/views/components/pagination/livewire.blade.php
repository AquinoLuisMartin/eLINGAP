@php
    $controlClass = 'inline-flex min-h-11 min-w-11 items-center justify-center rounded-md border border-[var(--line)] bg-[var(--white)] px-3 text-sm font-medium text-[var(--ink)] transition-colors hover:bg-[var(--paper)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--blue)] disabled:cursor-not-allowed disabled:opacity-40';
    $scrollToResults = 'document.getElementById('.\Illuminate\Support\Js::from($scrollTarget).')?.scrollIntoView({ block: "start" })';
@endphp

<div class="flex flex-wrap items-center justify-between gap-4 border-t border-[var(--line)] px-5 py-4">
    <p class="text-sm leading-6 text-[var(--muted)]" role="status" aria-live="polite">
        Showing <span class="font-medium tabular-nums text-[var(--ink)]">{{ number_format($paginator->firstItem()) }}</span>
        to <span class="font-medium tabular-nums text-[var(--ink)]">{{ number_format($paginator->lastItem()) }}</span>
        of <span class="font-medium tabular-nums text-[var(--ink)]">{{ number_format($paginator->total()) }}</span> results
    </p>

    <nav class="flex flex-wrap items-center gap-1" aria-label="{{ $label }}">
        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" x-on:click="{{ $scrollToResults }}" @disabled($paginator->onFirstPage()) class="{{ $controlClass }}" aria-label="Previous page">
            <x-admin.icon name="chevron-left" size="18" />
        </button>

        <div class="hidden flex-wrap items-center gap-1 md:flex">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span wire:key="pagination-{{ $paginator->getPageName() }}-gap-{{ $loop->index }}" class="inline-flex min-h-11 min-w-8 items-center justify-center text-sm text-[var(--muted)]" aria-hidden="true">{{ $element }}</span>
                @else
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span wire:key="pagination-{{ $paginator->getPageName() }}-page-{{ $page }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-md border border-[var(--blue)] bg-[var(--blue)] px-3 text-sm font-semibold tabular-nums text-white" aria-current="page" aria-label="Page {{ $page }}">{{ $page }}</span>
                        @else
                            <button type="button" wire:key="pagination-{{ $paginator->getPageName() }}-page-{{ $page }}" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" x-on:click="{{ $scrollToResults }}" class="{{ $controlClass }} tabular-nums" aria-label="Go to page {{ $page }}">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        <span class="px-2 text-sm tabular-nums text-[var(--muted)] md:hidden">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" x-on:click="{{ $scrollToResults }}" @disabled(! $paginator->hasMorePages()) class="{{ $controlClass }}" aria-label="Next page">
            <x-admin.icon name="chevron-right" size="18" />
        </button>
    </nav>
</div>
