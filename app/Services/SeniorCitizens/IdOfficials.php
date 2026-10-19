<?php

namespace App\Services\SeniorCitizens;

use App\Models\SeniorCitizen;
use App\Models\SystemConfiguration;
use App\Services\Benefits\BenefitAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class IdOfficials
{
    public function __construct(private PrivateUploads $uploads) {}

    public const KEYS = ['osca_head_name', 'municipal_mayor_name', 'osca_head_signature', 'municipal_mayor_signature', 'municipal_seal'];

    public function values(): array
    {
        return SystemConfiguration::query()->whereIn('key', self::KEYS)->pluck('value', 'key')->all();
    }

    public function save(array $names, array $uploads, array $remove = []): void
    {
        Gate::authorize('configureId', SeniorCitizen::class);
        $names = Validator::make($names, [
            'osca_head_name' => ['required', 'string', 'max:150'],
            'municipal_mayor_name' => ['required', 'string', 'max:150'],
        ])->validate();
        $uploads = Validator::make($uploads, collect(['osca_head_signature', 'municipal_mayor_signature', 'municipal_seal'])
            ->mapWithKeys(fn ($key) => [$key => ['nullable', 'image', 'mimes:jpeg,png', 'max:2048', 'dimensions:max_width=6000,max_height=6000']])->all())->validate();
        $paths = [];
        try {
            DB::transaction(function () use ($names, $uploads, $remove, &$paths): void {
                $values = $names;
                foreach ($uploads as $key => $file) {
                    if ($file) {
                        $paths[] = $values[$key] = $this->uploads->storeOfficialImage($file);
                    } elseif (in_array($key, $remove, true)) {
                        $values[$key] = null;
                    }
                }
                $changed = [];
                foreach ($values as $key => $value) {
                    $setting = SystemConfiguration::query()->where('key', $key)->lockForUpdate()->first() ?? new SystemConfiguration;
                    $setting->forceFill(['key' => $key, 'value' => $value, 'type' => 'string']);
                    if ($setting->exists && ! $setting->isDirty()) {
                        continue;
                    }
                    $setting->forceFill(['updated_by' => auth()->id()])->save();
                    $changed[] = $key;
                }
                if ($changed) {
                    BenefitAudit::record('senior_id.officials_updated', new SystemConfiguration, ['fields' => $changed]);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }
    }
}
