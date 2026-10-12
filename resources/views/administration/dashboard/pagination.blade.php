@if ($paginator->hasPages())
    <nav class="admin-pagination" aria-label="Pagination">
        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" @disabled($paginator->onFirstPage())>Previous</button>
        <span aria-live="polite">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" @disabled(! $paginator->hasMorePages())>Next</button>
    </nav>
@endif
