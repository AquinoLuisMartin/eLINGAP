{{-- Help and Support Page --}}
<section class="page-intro">
    <div>
        <p class="section-kicker">Support center</p>
        <h2>Help &amp; Support</h2>
        <p class="section-subtitle">Documentation, FAQs, and system support resources for eLINGAP administrators.</p>
    </div>
</section>

<section class="support-banner">
    <div>
        <span class="support-kicker">Need immediate assistance?</span>
        <h3>Contact OSCA IT Support</h3>
        <p>Monday–Friday, 8:00 AM–5:00 PM</p>
    </div>
    <div class="support-actions">
        <a class="support-button light" href="tel:+63448151234">
            <span class="support-symbol">☎</span> (044) 815-1234
        </a>
        <a class="support-button" href="mailto:it.support@elingap.gov.ph">
            <span class="support-symbol">✉</span> Email Support
        </a>
    </div>
</section>

<section class="support-section">
    <div class="support-section-heading">
        <div>
            <h3>Quick Reference Guides</h3>
            <p>Download operational guides for common administrator workflows.</p>
        </div>
        <x-admin.icon name="file-text" size="19" />
    </div>

    @php
        $guides = [
            ['icon' => 'clipboard-list', 'title' => 'Senior Registration Guide', 'description' => 'Step-by-step process for adding and verifying senior records.'],
            ['icon' => 'heart-pulse', 'title' => 'Benefits Disbursement Process', 'description' => 'Quarterly pension handling and approval checkpoints.'],
            ['icon' => 'message-square', 'title' => 'SMS Broadcast Manual', 'description' => 'Message templates, targeting, scheduling, and delivery checks.'],
            ['icon' => 'shield-check', 'title' => 'ID Renewal Workflow', 'description' => 'Document requirements and validation procedures.'],
            ['icon' => 'user-cog', 'title' => 'System Admin Manual', 'description' => 'User management, roles, permissions, and access reviews.'],
            ['icon' => 'archive', 'title' => 'Data Privacy & Security', 'description' => 'Policies and safeguards under Republic Act 10173.'],
        ];
    @endphp

    <div class="guide-grid">
        @foreach ($guides as $guide)
            <article class="guide-card" wire:key="guide-{{ $guide['title'] }}">
                <span class="guide-icon">
                    <x-admin.icon name="{{ $guide['icon'] }}" size="19" />
                </span>
                <h4>{{ $guide['title'] }}</h4>
                <p>{{ $guide['description'] }}</p>
                <button type="button" class="download-link" wire:click="triggerToast('{{ $guide['title'] }} PDF download started.')">
                    Download PDF <span>→</span>
                </button>
            </article>
        @endforeach
    </div>
</section>

<section class="support-section faq-section">
    <div class="support-section-heading">
        <div>
            <h3>Frequently Asked Questions</h3>
            <p>Answers to common administration questions.</p>
        </div>
        <x-admin.icon name="circle-help" size="19" />
    </div>

    @php
        $faqs = [
            ['question' => 'How do I register a senior citizen?', 'answer' => 'Open Senior Records Registry, select Add New Senior, complete the required identity and barangay fields, then submit the record for validation.'],
            ['question' => 'How do I send an SMS broadcast?', 'answer' => 'Open SMS Dispatcher, choose the target barangay, select a quick template or write a message, review the recipient estimate, and schedule the broadcast.'],
            ['question' => 'What happens when an OSCA ID expires?', 'answer' => 'The record appears in the validation queue. Review the renewal documents, update the expiration date, and mark the record as verified.'],
            ['question' => 'How do I approve benefits?', 'answer' => 'Open Benefits & Programs, select the current cycle, review eligible records, and confirm the approved payout batch.'],
            ['question' => 'How do I suspend a user?', 'answer' => 'Open User Accounts and use the Suspend action on the account row. The user immediately loses active access until reactivated.'],
            ['question' => 'Can I export the masterlist?', 'answer' => 'Yes. Open Senior Records Registry and select Export CSV. The current filtered registry view is downloaded for review.'],
        ];
    @endphp

    <div class="faq-list">
        @foreach ($faqs as $index => $faq)
            <div class="faq-item {{ $openFaq === $index ? 'is-open' : '' }}" wire:key="faq-{{ $index }}">
                <button type="button" wire:click="toggleFaq({{ $index }})">
                    <span>{{ $faq['question'] }}</span>
                    <x-admin.icon name="chevron-down" size="17" />
                </button>
                @if ($openFaq === $index)
                    <p>{{ $faq['answer'] }}</p>
                @endif
            </div>
        @endforeach
    </div>
</section>

<section class="system-info-card">
    <div class="system-info-title">
        <x-admin.icon name="gauge" size="19" />
        <div>
            <h3>System Information</h3>
            <p>Environment and service health details</p>
        </div>
    </div>
    <div class="system-info-grid">
        <div>
            <small>Application version</small>
            <strong>eLINGAP v2.4.1</strong>
        </div>
        <div>
            <small>Municipality</small>
            <strong>Santa Maria, Bulacan</strong>
        </div>
        <div>
            <small>Database health</small>
            <strong class="health-value"><i></i> Healthy - 98.2% uptime</strong>
        </div>
        <div>
            <small>Last updated</small>
            <strong>Sep 14, 2024</strong>
        </div>
    </div>
</section>
