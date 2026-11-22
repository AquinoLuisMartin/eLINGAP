<section class="page-intro"><div><p class="section-kicker">Support center</p><h2>Help &amp; Support</h2></div></section>
<div class="panel"><div class="panel-heading"><div><h3>Administrator Workflows</h3></div></div>
<div class="activity-list">
    @can('create', \App\Models\SeniorCitizen::class)
        <a class="task-row" href="{{ route('senior-citizens.create') }}">Register a senior citizen</a>
    @else
        <a class="task-row" href="{{ route('registrations.index') }}">Review senior registrations</a>
    @endcan
    <a class="task-row" href="{{ route('benefits.workspace') }}">Review benefit reports and payments</a>
    <a class="task-row" href="{{ route('administration.users.index') }}">Manage user accounts</a>
</div></div>
