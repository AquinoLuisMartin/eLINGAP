{{-- Overview Dashboard Page --}}
<section class="welcome-row">
    <div>
        <p class="section-kicker">{{ now()->format('l, F j, Y') }}</p>
        <h2>Good morning, Maria <span>✦</span></h2>
        <p class="section-subtitle">Here is what's happening across the eLINGAP registry today.</p>
    </div>
</section>

<section class="metric-grid">
    <div class="metric-card">
        <div class="metric-icon blue">
            <x-admin.icon name="users" size="19" />
        </div>
        <div class="metric-label">Total Seniors Reached</div>
        <div class="metric-value">18,427</div>
        <div class="metric-bottom">
            <span class="trend">
                <x-admin.icon name="arrow-up-right" size="14" /> +8.4%
            </span>
            <span>vs. last quarter</span>
        </div>
        <div class="metric-progress blue"><i style="width: 82%;"></i></div>
    </div>

    <div class="metric-card">
        <div class="metric-icon mint">
            <x-admin.icon name="heart-pulse" size="19" />
        </div>
        <div class="metric-label">Active Benefits Scheduled</div>
        <div class="metric-value">6,840</div>
        <div class="metric-bottom">
            <span class="trend">
                <x-admin.icon name="arrow-up-right" size="14" /> +12.1%
            </span>
            <span>of 7,200 target</span>
        </div>
        <div class="metric-progress mint"><i style="width: 91%;"></i></div>
    </div>

    <div class="metric-card">
        <div class="metric-icon violet">
            <x-admin.icon name="message-square" size="19" />
        </div>
        <div class="metric-label">SMS Delivery Success Rate</div>
        <div class="metric-value">98.6%</div>
        <div class="metric-bottom">
            <span class="trend">
                <x-admin.icon name="arrow-up-right" size="14" /> +1.8%
            </span>
            <span>this month</span>
        </div>
        <div class="metric-progress violet"><i style="width: 98%;"></i></div>
    </div>

    <div class="metric-card">
        <div class="metric-icon amber">
            <x-admin.icon name="clipboard-list" size="19" />
        </div>
        <div class="metric-label">Pending ID Validations</div>
        <div class="metric-value">247</div>
        <div class="metric-bottom">
            <span class="trend down">
                <x-admin.icon name="arrow-down-right" size="14" /> -14.6%
            </span>
            <span>needs review</span>
        </div>
        <div class="metric-progress amber"><i style="width: 46%;"></i></div>
    </div>
</section>

<section class="dashboard-grid">
    {{-- Distribution Chart Panel --}}
    <div class="panel chart-panel">
        <div class="panel-heading">
            <div>
                <h3>Pension &amp; Benefit Distributions vs. Target</h3>
                <p>Distribution performance across all barangays</p>
            </div>
            <div class="segmented">
                <button type="button" class="{{ $period === 'Monthly' ? 'selected' : '' }}" wire:click="$set('period', 'Monthly')">Monthly</button>
                <button type="button" class="{{ $period === 'Quarterly' ? 'selected' : '' }}" wire:click="$set('period', 'Quarterly')">Quarterly</button>
            </div>
        </div>

        <div class="chart-toolbar">
            <span>
                <i class="legend-dot actual"></i> Actual distribution
                <i class="legend-dot target"></i> Target
            </span>
            <div class="chart-switch">
                <button type="button" class="{{ $chartType === 'Area' ? 'selected' : '' }}" wire:click="$set('chartType', 'Area')">
                    <x-admin.icon name="activity" size="14" /> Area
                </button>
                <button type="button" class="{{ $chartType === 'Bar' ? 'selected' : '' }}" wire:click="$set('chartType', 'Bar')">
                    <x-admin.icon name="bar-chart-3" size="14" /> Bar
                </button>
            </div>
        </div>

        @php
            $chartData = $period === 'Monthly' ? [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                'actual' => [58, 72, 64, 81, 76, 88, 68, 91, 84, 94, 89, 97],
                'target' => [64, 70, 72, 76, 80, 82, 84, 87, 89, 91, 94, 96],
            ] : [
                'labels' => ['Q1', 'Q2', 'Q3', 'Q4'],
                'actual' => [65, 82, 81, 93],
                'target' => [69, 79, 86, 94],
            ];

            $count = count($chartData['labels']);
            $pointsActual = [];
            $pointsTarget = [];
            for ($i = 0; $i < $count; $i++) {
                $x = ($i * 100) / max(1, $count - 1);
                $yA = 100 - $chartData['actual'][$i];
                $yT = 100 - $chartData['target'][$i];
                $pointsActual[] = "{$x},{$yA}";
                $pointsTarget[] = "{$x},{$yT}";
            }
            $actualPolyline = implode(' ', $pointsActual);
            $targetPolyline = implode(' ', $pointsTarget);
            $areaPoints = "0,100 {$actualPolyline} 100,100";
        @endphp

        @if ($chartType === 'Area')
            <div class="area-chart">
                <div class="chart-y">
                    <span>100%</span><span>75%</span><span>50%</span><span>25%</span><span>0%</span>
                </div>
                <div class="area-chart-stage">
                    <div class="grid-lines">
                        <i></i><i></i><i></i><i></i>
                    </div>
                    <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-label="Actual distribution and target area chart">
                        <polygon class="area-fill" points="{{ $areaPoints }}" />
                        <polyline class="target-line" points="{{ $targetPolyline }}" />
                        <polyline class="actual-line" points="{{ $actualPolyline }}" />
                        @foreach ($chartData['actual'] as $idx => $val)
                            @php $cx = ($idx * 100) / max(1, $count - 1); $cy = 100 - $val; @endphp
                            <circle class="actual-point" cx="{{ $cx }}" cy="{{ $cy }}" r="1.2" />
                        @endforeach
                    </svg>
                    <div class="area-labels">
                        @foreach ($chartData['labels'] as $label)
                            <span>{{ $label }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <div class="distribution-chart bar">
                <div class="chart-y">
                    <span>100%</span><span>75%</span><span>50%</span><span>25%</span><span>0%</span>
                </div>
                <div class="chart-area">
                    <div class="grid-lines">
                        <i></i><i></i><i></i><i></i>
                    </div>
                    <div class="bars">
                        @foreach ($chartData['actual'] as $idx => $act)
                            <div class="bar-group">
                                <i style="height: {{ $act }}%;"></i>
                                <b style="height: {{ $chartData['target'][$idx] }}%;"></b>
                                <span>{{ $chartData['labels'][$idx] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Recent Activity Panel --}}
    <div class="panel activity-panel">
        <div class="panel-heading">
            <div>
                <h3>Recent Activity</h3>
                <p>Latest changes in the registry</p>
            </div>
            <button type="button" class="text-button" wire:click="navigate('system-logs')">View all</button>
        </div>
        <div class="activity-list">
            <div class="activity-item">
                <span class="activity-icon success">
                    <x-admin.icon name="check" size="15" />
                </span>
                <div>
                    <strong>New record verified</strong>
                    <small>Maria Lourdes Santos · Poblacion</small>
                </div>
                <time>8 min ago</time>
            </div>
            <div class="activity-item">
                <span class="activity-icon blue">
                    <x-admin.icon name="send" size="15" />
                </span>
                <div>
                    <strong>SMS campaign delivered</strong>
                    <small>Pension payout reminder · 96.8%</small>
                </div>
                <time>24 min ago</time>
            </div>
            <div class="activity-item">
                <span class="activity-icon violet">
                    <x-admin.icon name="pencil" size="15" />
                </span>
                <div>
                    <strong>Program details updated</strong>
                    <small>Medical Assistance Program</small>
                </div>
                <time>1 hr ago</time>
            </div>
            <div class="activity-item">
                <span class="activity-icon amber">
                    <x-admin.icon name="alert-triangle" size="15" />
                </span>
                <div>
                    <strong>Validation queue increased</strong>
                    <small>13 new submissions</small>
                </div>
                <time>2 hrs ago</time>
            </div>
        </div>
    </div>
</section>

<section class="dashboard-grid lower-grid">
    {{-- Barangay Panel --}}
    <div class="panel barangay-panel">
        <div class="panel-heading">
            <div>
                <h3>Registry by Barangay</h3>
                <p>Senior citizens reached this quarter</p>
            </div>
            <button type="button" class="icon-button" aria-label="Barangay options">
                <x-admin.icon name="more-horizontal" size="18" />
            </button>
        </div>
        <div class="barangay-list">
            <div class="bar-row">
                <div><span>Poblacion</span><strong>2,418</strong></div>
                <div class="bar-track"><i style="width: 86%;"></i></div>
            </div>
            <div class="bar-row">
                <div><span>Sta. Cruz</span><strong>1,985</strong></div>
                <div class="bar-track"><i style="width: 72%;"></i></div>
            </div>
            <div class="bar-row">
                <div><span>Kaybanban</span><strong>1,754</strong></div>
                <div class="bar-track"><i style="width: 64%;"></i></div>
            </div>
            <div class="bar-row">
                <div><span>San Gabriel</span><strong>1,596</strong></div>
                <div class="bar-track"><i style="width: 58%;"></i></div>
            </div>
        </div>
    </div>

    {{-- Tasks Panel --}}
    <div class="panel tasks-panel">
        <div class="panel-heading">
            <div>
                <h3>Today's Priorities</h3>
                <p>Items that need your attention</p>
            </div>
            <span class="soft-badge blue">3 open</span>
        </div>
        <button type="button" class="task-row" wire:click="$set('recordFilter', 'Pending'); navigate('records')">
            <span class="task-icon amber">
                <x-admin.icon name="clipboard-list" size="16" />
            </span>
            <span>
                <strong>Review pending ID validations</strong>
                <small>247 records</small>
            </span>
            <x-admin.icon name="chevron-right" size="16" />
        </button>
        <button type="button" class="task-row" wire:click="navigate('sms')">
            <span class="task-icon blue">
                <x-admin.icon name="message-square" size="16" />
            </span>
            <span>
                <strong>Confirm SMS broadcast schedule</strong>
                <small>2 campaigns</small>
            </span>
            <x-admin.icon name="chevron-right" size="16" />
        </button>
        <button type="button" class="task-row" wire:click="$set('logCategory', 'Audit Trail'); navigate('system-logs')">
            <span class="task-icon violet">
                <x-admin.icon name="shield-check" size="16" />
            </span>
            <span>
                <strong>Complete quarterly audit review</strong>
                <small>Due today</small>
            </span>
            <x-admin.icon name="chevron-right" size="16" />
        </button>
    </div>
</section>
