<?php

namespace App\Http\Controllers\Programs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Programs\StoreProgramRequest;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Program::class);

        return view('programs.index', ['programs' => Program::query()->latest('id')->paginate(15)]);
    }

    public function create(): View
    {
        Gate::authorize('create', Program::class);

        return view('programs.create');
    }

    public function store(StoreProgramRequest $request): RedirectResponse
    {
        $program = Program::create([...$request->validated(), 'created_by' => $request->user()->id, 'status' => 'UPCOMING']);

        return redirect()->route('programs.show', $program)->with('status', 'Program created.');
    }

    public function show(Program $program): View
    {
        Gate::authorize('view', $program);

        return view('programs.show', ['program' => $program->loadCount('applications')]);
    }
}
