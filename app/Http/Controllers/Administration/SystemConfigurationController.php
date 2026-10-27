<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\UpdateSystemConfigurationRequest;
use App\Models\SystemConfiguration;
use App\Services\Benefits\BenefitAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SystemConfigurationController extends Controller
{
    public function update(UpdateSystemConfigurationRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated('settings') as $key => $value) {
                $setting = SystemConfiguration::query()->firstOrCreate(['key' => $key]);
                $setting = SystemConfiguration::query()->lockForUpdate()->findOrFail($setting->id);
                $setting->forceFill(['value' => $value === null ? null : trim($value), 'type' => 'string']);
                if (! $setting->isDirty()) {
                    continue;
                }
                $setting->forceFill(['updated_by' => $request->user()->id])->save();
                BenefitAudit::record('system_configuration.updated', $setting, ['key' => $key]);
            }
        });

        return redirect()->route('administration.dashboard', ['tab' => 'configuration'])->with('status', 'Office settings saved.');
    }
}
