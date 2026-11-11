@extends('layouts.app')

@section('content')
<x-staff.page-header title="Programs" description="Browse program details, budgets, and availability.">
    @can('create', App\Models\Program::class)
        <a class="primary-button" href="{{ route('administration.programs.create') }}">Create program</a>
    @endcan
</x-staff.page-header>
<section class="staff-results-panel rounded-lg border bg-white" aria-labelledby="program-list-heading">
    <div class="staff-panel-heading flex flex-wrap items-center justify-between gap-3">
        <h3 id="program-list-heading">All programs</h3>
        <p>{{ number_format($programs->total()) }} programs</p>
    </div>
    <x-staff.table label="Programs">
        <table class="w-full text-left text-sm">
            <thead><tr><th scope="col">Program</th><th scope="col">Agency</th><th scope="col">Budget</th><th scope="col">Status</th><th scope="col" class="text-right">Action</th></tr></thead>
            <tbody>
                @forelse ($programs as $program)
                    <tr>
                        <td class="font-medium">{{ $program->name }}</td>
                        <td>{{ $program->agency }}</td>
                        <td class="whitespace-nowrap tabular-nums">{{ \App\Support\PhilippineCurrency::format($program->budget) }}</td>
                        <td><x-badge :status="$program->status" /></td>
                        <td class="text-right"><a class="secondary-button" href="{{ route('programs.show', $program) }}">View details<span class="sr-only"> for {{ $program->name }}</span></a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-8 text-center text-[var(--muted)]">No programs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-staff.table>
    @if ($programs->hasPages())<div class="staff-pagination">{{ $programs->links() }}</div>@endif
</section>
@endsection
