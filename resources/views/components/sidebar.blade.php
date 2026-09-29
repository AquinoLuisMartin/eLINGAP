@props(['active' => 'Masterlist'])

<aside class="group fixed left-0 top-0 z-50 flex h-screen w-16 flex-col overflow-hidden border-r border-slate-200 bg-white shadow-sm transition-all duration-300 ease-in-out hover:w-64 dark:border-slate-800 dark:bg-slate-900">
    <div class="flex h-[65px] shrink-0 items-center gap-3 border-b border-slate-200 px-4 dark:border-slate-800">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#1d3b65] text-sm font-bold text-white">eL</div>
        <div class="min-w-0 whitespace-nowrap opacity-0 transition-opacity duration-300 group-hover:opacity-100">
            <p class="text-sm font-bold text-slate-900 dark:text-white">GabayDocs</p>
            <p class="text-[10px] font-medium text-slate-500 dark:text-slate-400">OSCA Santa Maria</p>
        </div>
    </div>
    <nav class="flex-1 space-y-1 px-2 py-5" aria-label="Primary navigation">
        @foreach ([['label' => 'Dashboard', 'path' => 'dashboard-preview', 'icon' => 'grid'], ['label' => 'Masterlist', 'path' => 'masterlist*', 'icon' => 'users'], ['label' => 'Registrations', 'path' => 'registrations*', 'icon' => 'clipboard'], ['label' => 'Payouts', 'path' => 'payouts*', 'icon' => 'heart'], ['label' => 'Messaging', 'path' => 'messaging*', 'icon' => 'message'], ['label' => 'Reports', 'path' => 'reports*', 'icon' => 'chart']] as $item)
            @php($isActive = request()->is($item['path']))
            <a href="/{{ rtrim($item['path'], '*') }}" class="flex h-10 items-center rounded-xl px-3 text-sm font-medium {{ $isActive ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-700 hover:bg-slate-100 hover:text-blue-700' }}" aria-current="{{ $isActive ? 'page' : 'false' }}">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center">
                    @switch($item['icon'])
                        @case('grid') <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg> @break
                        @case('users') <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 20c0-3.3 3.1-6 7-6s7 2.7 7 6H2Z"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M17 14c2.8.5 5 2.8 5 6h-4"/></svg> @break
                        @case('clipboard') <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 3h6a2 2 0 0 1 2 2h2a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2a2 2 0 0 1 2-2Zm0 4h6V5H9v2Zm-3 3v9h12v-9H6Z"/><path d="M8 13h8v1.5H8zm0 3h6v1.5H8z"/></svg> @break
                        @case('heart') <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 20-1.4-1.3C5.6 14.2 2 10.9 2 7a5 5 0 0 1 9-3 5 5 0 0 1 9 3c0 3.9-3.6 7.2-8.6 11.7L12 20Z"/></svg> @break
                        @case('message') <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20 4H4a2 2 0 0 0-2 2v13l4-3h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm-3 7H7V9h10v2Zm-3 3H7v-2h7v2Z"/></svg> @break
                        @case('chart') <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 20V4h2v16H4Zm5 0V10h2v10H9Zm5 0V7h2v13h-2Zm5 0V2h2v18h-2Z"/></svg> @break
                    @endswitch
                </span>
                <span class="ml-4 whitespace-nowrap opacity-0 transition-opacity duration-300 group-hover:opacity-100">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
    <div class="space-y-4 border-t border-slate-200 px-2 py-4 dark:border-slate-800">
        <a href="#" class="flex h-10 items-center rounded-xl px-3 text-sm font-medium text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"><span class="flex h-6 w-6 shrink-0 items-center justify-center"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M9.8 9a2.3 2.3 0 1 1 3.8 1.7c-.9.7-1.6 1.1-1.6 2.3M12 16h.01"/></svg></span><span class="ml-4 whitespace-nowrap opacity-0 transition-opacity duration-300 group-hover:opacity-100">Help</span></a>
        <div class="flex items-center gap-3 px-2"><div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">MA</div><div class="whitespace-nowrap opacity-0 transition-opacity duration-300 group-hover:opacity-100"><p class="text-xs font-semibold text-slate-900 dark:text-white">Maria A.</p><p class="text-[10px] text-slate-500 dark:text-slate-400">OSCA Staff</p></div></div>
    </div>
</aside>
