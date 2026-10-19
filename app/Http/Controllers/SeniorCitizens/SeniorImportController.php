<?php

namespace App\Http\Controllers\SeniorCitizens;

use App\Http\Controllers\Controller;
use App\Http\Requests\SeniorCitizens\PreviewSeniorImportRequest;
use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Services\SeniorCitizens\SeniorImport;
use App\Services\SeniorCitizens\SeniorImportReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeniorImportController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', SeniorCitizen::class);

        return view('senior-citizens.imports.create', ['barangays' => Barangay::query()->where('is_active', true)->orderBy('name')->get(['name', 'code'])]);
    }

    public function preview(PreviewSeniorImportRequest $request, SeniorImportReader $reader, SeniorImport $importer): RedirectResponse
    {
        $source = $reader->read($request->file('file'));
        $prepared = $importer->prepare($source);
        $token = (string) Str::uuid();
        $stored = Cache::put($this->key($request, $token), Crypt::encryptString(json_encode(['source' => $source, 'prepared' => $prepared], JSON_THROW_ON_ERROR)), now()->addMinutes(30));
        abort_unless($stored, 503, 'The preview could not be saved. Please try again.');

        return redirect()->route('registrations.import.show', $token);
    }

    public function show(Request $request, string $token): View
    {
        Gate::authorize('create', SeniorCitizen::class);
        $payload = $this->payload($request, $token);
        $page = max(1, (int) $request->query('page', 1));
        $prepared = $payload['prepared'];
        $rows = new LengthAwarePaginator(array_slice($prepared['rows'], ($page - 1) * 50, 50), count($prepared['rows']), 50, $page, ['path' => $request->url()]);

        return view('senior-citizens.imports.preview', compact('prepared', 'rows', 'token'));
    }

    public function store(Request $request, string $token, SeniorImport $importer): RedirectResponse
    {
        Gate::authorize('create', SeniorCitizen::class);
        $request->validate(['reviewed' => ['required', 'accepted']]);
        $result = Cache::lock($this->key($request, $token).':lock', 120)->get(function () use ($request, $token, $importer): array {
            $payload = $this->payload($request, $token);
            $result = $importer->commit($payload['source']);
            Cache::forget($this->key($request, $token));

            return $result;
        });
        abort_if($result === false, 409, 'This import is already being processed.');

        return redirect()->route('registrations.index')->with('status', "Import complete: {$result['created']} records added for review; {$result['skipped']} duplicates skipped.");
    }

    public function destroy(Request $request, string $token): RedirectResponse
    {
        Gate::authorize('create', SeniorCitizen::class);
        $discarded = Cache::lock($this->key($request, $token).':lock', 120)->get(function () use ($request, $token): bool {
            Cache::forget($this->key($request, $token));

            return true;
        });
        abort_if($discarded === false, 409, 'This import is already being processed.');

        return redirect()->route('registrations.import.create')->with('status', 'Import preview discarded.');
    }

    public function template(string $format): StreamedResponse
    {
        Gate::authorize('create', SeniorCitizen::class);
        abort_unless(in_array($format, ['csv', 'xlsx', 'xls'], true), 404);

        return response()->streamDownload(function () use ($format): void {
            if ($format === 'csv') {
                $stream = fopen('php://output', 'wb');
                fwrite($stream, "\xEF\xBB\xBF");
                fputcsv($stream, SeniorImportReader::COLUMNS, ',', '"', '');
                fclose($stream);

                return;
            }
            $book = new Spreadsheet;
            $sheet = $book->getActiveSheet();
            $sheet->setTitle('Senior registrations');
            foreach (SeniorImportReader::COLUMNS as $index => $heading) {
                $sheet->setCellValueExplicit([$index + 1, 1], $heading, DataType::TYPE_STRING);
                $sheet->getColumnDimensionByColumn($index + 1)->setWidth(24);
            }
            $sheet->getStyle('A2:N501')->getNumberFormat()->setFormatCode('@');
            $sheet->freezePane('A2');
            try {
                IOFactory::createWriter($book, $format === 'xlsx' ? 'Xlsx' : 'Xls')->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, 'senior-import-template.'.$format, ['Content-Type' => match ($format) {
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel', default => 'text/csv; charset=UTF-8',
        }]);
    }

    private function key(Request $request, string $token): string
    {
        return 'senior-import:'.$request->user()->id.':'.$token;
    }

    private function payload(Request $request, string $token): array
    {
        $encrypted = Cache::get($this->key($request, $token));
        if (! is_string($encrypted)) {
            abort(response()->view('senior-citizens.imports.expired', [], 410));
        }

        return json_decode(Crypt::decryptString($encrypted), true, flags: JSON_THROW_ON_ERROR);
    }
}
