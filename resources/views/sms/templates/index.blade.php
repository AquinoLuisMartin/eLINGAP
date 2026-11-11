@extends('layouts.app')
@section('content')
@php($templateData = $templates->map(fn ($template) => ['id' => (string) $template->id, 'title' => $template->name, 'body' => $template->body, 'active' => (bool) $template->is_active])->values())
<div x-data="smsTemplateEditor({ key: @js('elingap-sms-templates-'.auth()->id()) })" data-templates="{{ json_encode($templateData, JSON_THROW_ON_ERROR) }}"
    x-on:sms-templates-updated.window="refreshTemplates($event)" x-on:storage.window="refreshTemplates($event)">
    <x-workspace-navigation section="messaging" />
    <x-staff.page-header title="SMS templates" description="Maintain reusable messages for individual notices and broadcasts.">
        <a class="primary-button" href="{{ route('sms.templates.create') }}">New template</a>
    </x-staff.page-header>
    <p class="mb-4 text-sm text-slate-500">Template edits are saved in this browser and synced to Quick template. Database records stay unchanged.</p>
    <p x-cloak x-show="notice" x-text="notice" role="status" class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200"></p>
    <div class="grid gap-3">
        <template x-for="item in displayedTemplates" :key="item.id">
            <article class="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="min-w-0 wrap-anywhere font-semibold" x-text="item.title"></h2>
                    <div class="flex items-center gap-3">
                        <span class="ui-label" x-text="item.active ? 'Active' : 'Inactive'"></span>
                        <button type="button" class="secondary-button" x-on:click="editTemplate(item)" :aria-label="'Edit ' + item.title">Edit</button>
                    </div>
                </div>
                <p class="mt-3 whitespace-pre-wrap wrap-anywhere text-sm" x-text="item.body"></p>
            </article>
        </template>
        <p x-cloak x-show="displayedTemplates.length === 0" class="rounded-lg border bg-white p-6 dark:bg-slate-900">No templates yet.</p>
    </div>
    <div class="mt-4">{{ $templates->links() }}</div>
    <dialog x-ref="editDialog" aria-labelledby="edit-template-title" class="m-auto max-h-[85dvh] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-xl border border-slate-200 bg-white p-6 text-slate-900 shadow-xl backdrop:bg-slate-950/50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
        <template x-if="draft">
            <form x-on:submit.prevent="saveTemplate()" class="flex flex-col gap-5">
                <h2 id="edit-template-title" class="text-xl font-semibold">Edit template</h2>
                <label class="field-label">Template Title<input x-model="draft.title" maxlength="120" required autofocus></label>
                <label class="field-label">Message Body<textarea x-model="draft.body" rows="7" maxlength="1600" required></textarea></label>
                <label class="flex items-center gap-3 text-sm"><input type="checkbox" x-model="draft.active">Active</label>
                <p x-cloak x-show="error" x-text="error" role="alert" class="text-sm text-red-700 dark:text-red-300"></p>
                <div class="flex flex-wrap justify-end gap-3">
                    <button type="button" class="secondary-button" x-on:click="$refs.editDialog.close()">Cancel</button>
                    <button type="submit" class="primary-button">Save changes</button>
                </div>
            </form>
        </template>
    </dialog>
</div>
@endsection
