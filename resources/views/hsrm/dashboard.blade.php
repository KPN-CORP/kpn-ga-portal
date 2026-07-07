@extends('layouts.hsrm-app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<style>
    .chart-container {
        position: relative;
        width: 100%;
        height: 100%;
    }
    .chart-container canvas {
        display: block;
        width: 100% !important;
        height: 100% !important;
    }
    .chart-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid rgba(229,231,235,0.5);
        box-shadow: 0 4px 20px rgba(0,0,0,0.03), 0 1px 3px rgba(0,0,0,0.04);
        padding: 1.25rem;
        transition: all 0.25s ease;
    }
    .chart-card:hover {
        box-shadow: 0 8px 30px rgba(0,0,0,0.06), 0 2px 6px rgba(0,0,0,0.04);
        border-color: rgba(59,130,246,0.2);
    }
    .stat-card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid rgba(229,231,235,0.5);
        padding: 1.25rem 1rem;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.06);
        border-color: rgba(59,130,246,0.15);
    }
    .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .chart-title {
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-align: center;
        margin-bottom: 0.75rem;
    }
    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1f2937;
        letter-spacing: -0.01em;
    }
    .section-divider {
        width: 4px;
        height: 24px;
        border-radius: 4px;
        margin-right: 12px;
    }
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-verified { background: #d1fae5; color: #059669; }
    .status-rejected { background: #fee2e2; color: #dc2626; }
    .recent-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(229,231,235,0.4);
        padding-bottom: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .recent-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    .stat-link {
        display: block;
        text-decoration: none;
        transition: opacity 0.15s ease;
    }
    .stat-link:hover {
        opacity: 0.8;
    }
</style>
@endpush

@section('content')
{{-- Filter --}}
<div class="flex flex-wrap justify-between items-center mb-6 gap-3">
    <div class="flex items-center space-x-3">
        <label class="text-sm font-medium text-gray-700">View:</label>
        <select id="viewFilter" class="border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 bg-white shadow-sm">
            <option value="certificates" {{ $view == 'certificates' ? 'selected' : '' }}>📄 Certificates</option>
            <option value="equipments" {{ $view == 'equipments' ? 'selected' : '' }}>🔧 Equipments</option>
            <option value="all" {{ $view == 'all' ? 'selected' : '' }}>📊 All</option>
        </select>
    </div>
    <div class="text-sm text-gray-500 bg-gray-50/80 px-4 py-2 rounded-full border border-gray-200/60">
        @if($view == 'certificates')
            Showing <strong>Certificates</strong> only
        @elseif($view == 'equipments')
            Showing <strong>Equipments</strong> only
        @else
            Showing <strong>All</strong> data
        @endif
    </div>
</div>

{{-- Stats Cards --}}
@if($view == 'certificates' || $view == 'all')
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">Total Certificates</div>
            <div class="stat-icon bg-blue-50 text-blue-500">
                <i class="fas fa-file-alt"></i>
            </div>
        </div>
        <a href="{{ route('hsrm.certificates.filter', 'total') }}" class="stat-link text-2xl font-bold mt-1.5">
            {{ $certData['total'] ?? 0 }}
        </a>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">Active</div>
            <div class="stat-icon bg-green-50 text-green-500">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <a href="{{ route('hsrm.certificates.filter', 'active') }}" class="stat-link text-2xl font-bold text-green-600 mt-1.5">
            {{ $certData['active'] ?? 0 }}
        </a>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">Warning</div>
            <div class="stat-icon bg-yellow-50 text-yellow-500">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
        <a href="{{ route('hsrm.certificates.filter', 'warning') }}" class="stat-link text-2xl font-bold text-yellow-600 mt-1.5">
            {{ $certData['warning'] ?? 0 }}
        </a>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">Expired</div>
            <div class="stat-icon bg-red-50 text-red-500">
                <i class="fas fa-times-circle"></i>
            </div>
        </div>
        <a href="{{ route('hsrm.certificates.filter', 'expired') }}" class="stat-link text-2xl font-bold text-red-600 mt-1.5">
            {{ $certData['expired'] ?? 0 }}
        </a>
    </div>
</div>
@endif

@if($view == 'equipments' || $view == 'all')
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">Total Items</div>
            <div class="stat-icon bg-purple-50 text-purple-500">
                <i class="fas fa-fire-extinguisher"></i>
            </div>
        </div>
        <a href="{{ route('hsrm.equipments.filter', 'total') }}" class="stat-link text-2xl font-bold mt-1.5">
            {{ $eqData['total_items_all'] ?? 0 }}
        </a>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">Active Items</div>
            <div class="stat-icon bg-green-50 text-green-500">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <a href="{{ route('hsrm.equipments.filter', 'active') }}" class="stat-link text-2xl font-bold text-green-600 mt-1.5">
            {{ $eqData['total_items_active'] ?? 0 }}
        </a>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">Warning Items</div>
            <div class="stat-icon bg-yellow-50 text-yellow-500">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
        <a href="{{ route('hsrm.equipments.filter', 'warning') }}" class="stat-link text-2xl font-bold text-yellow-600 mt-1.5">
            {{ $eqData['total_items_warning'] ?? 0 }}
        </a>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">Expired Items</div>
            <div class="stat-icon bg-red-50 text-red-500">
                <i class="fas fa-times-circle"></i>
            </div>
        </div>
        <a href="{{ route('hsrm.equipments.filter', 'expired') }}" class="stat-link text-2xl font-bold text-red-600 mt-1.5">
            {{ $eqData['total_items_expired'] ?? 0 }}
        </a>
    </div>
</div>
@endif

{{-- Charts Section --}}
@if($view == 'certificates' || $view == 'all')
<div class="mb-8">
    <div class="flex items-center mb-4">
        <div class="section-divider bg-blue-500"></div>
        <h3 class="section-title">📄 Certificates Analytics</h3>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
        <div class="chart-card">
            <div class="chart-title">Status</div>
            <div class="chart-container" style="height:300px;">
                <canvas id="certStatusChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <div class="chart-title">Recommendation</div>
            <div class="chart-container" style="height:300px;">
                <canvas id="certRecommendChart"></canvas>
            </div>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-title">By Area</div>
        <div class="chart-container" style="height:420px;">
            <canvas id="certAreaChart"></canvas>
        </div>
    </div>
</div>
@endif

@if($view == 'equipments' || $view == 'all')
<div class="mb-8">
    <div class="flex items-center mb-4">
        <div class="section-divider bg-purple-500"></div>
        <h3 class="section-title">🔧 Equipments Analytics</h3>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
        <div class="chart-card">
            <div class="chart-title">Status (Total Items)</div>
            <div class="chart-container" style="height:300px;">
                <canvas id="eqStatusChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <div class="chart-title">Recommendation (Total Items)</div>
            <div class="chart-container" style="height:300px;">
                <canvas id="eqRecommendChart"></canvas>
            </div>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-title">By Area</div>
        <div class="chart-container" style="height:420px;">
            <canvas id="eqAreaChart"></canvas>
        </div>
    </div>
</div>
@endif

{{-- Recent Items --}}
@if($view == 'certificates' || $view == 'all')
<div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200/60 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-800">📋 Recent Certificates</h3>
        <span class="text-xs text-gray-400">Last 10</span>
    </div>
    <div class="space-y-1">
        @forelse($recentCerts as $cert)
            <div class="recent-item">
                <div>
                    <span class="font-medium text-gray-800">{{ $cert->employee_name }}</span>
                    <span class="text-xs text-gray-500 ml-2">{{ $cert->certificateType->name ?? '-' }}</span>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="status-badge 
                        @if($cert->status_verif == 'pending') status-pending
                        @elseif($cert->status_verif == 'verified') status-verified
                        @else status-rejected @endif">
                        {{ ucfirst($cert->status_verif) }}
                    </span>
                    <span class="text-xs text-gray-400">{{ $cert->updated_at->diffForHumans() }}</span>
                </div>
            </div>
        @empty
            <p class="text-gray-500 text-center py-4">No certificates found.</p>
        @endforelse
    </div>
</div>
@endif

@if($view == 'equipments' || $view == 'all')
<div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200/60">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-800">🔧 Recent Equipments</h3>
        <span class="text-xs text-gray-400">Last 10</span>
    </div>
    <div class="space-y-1">
        @forelse($recentEqs as $eq)
            <div class="recent-item">
                <div>
                    <span class="font-medium text-gray-800">{{ $eq->name }}</span>
                    <span class="text-xs text-gray-500 ml-2">{{ $eq->equipmentType->name ?? '-' }}</span>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="status-badge 
                        @if($eq->status_verif == 'pending') status-pending
                        @elseif($eq->status_verif == 'verified') status-verified
                        @else status-rejected @endif">
                        {{ ucfirst($eq->status_verif) }}
                    </span>
                    <span class="text-xs text-gray-400">{{ $eq->updated_at->diffForHumans() }}</span>
                </div>
            </div>
        @empty
            <p class="text-gray-500 text-center py-4">No equipments found.</p>
        @endforelse
    </div>
</div>
@endif

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Filter
        document.getElementById('viewFilter').addEventListener('change', function() {
            const view = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('view', view);
            window.location.href = url.toString();
        });

        // Register plugin
        Chart.register(ChartDataLabels);

        // --- Pie chart config (label di dalam, tooltip mati) ---
        const pieOptions = {
            type: 'pie',
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 12, weight: '500' },
                            color: '#374151'
                        }
                    },
                    tooltip: { enabled: false },
                    datalabels: {
                        color: '#ffffff',
                        font: { weight: 'bold', size: 14 },
                        formatter: function(value, ctx) {
                            let total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return value + '\n' + percentage + '%';
                        },
                        textAlign: 'center',
                        offset: 0,
                        display: function(context) {
                            return context.dataset.data[context.dataIndex] > 0;
                        }
                    }
                }
            }
        };

        // --- Bar chart config (label di atas, tooltip mati) ---
        const barOptions = {
            type: 'bar',
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        top: 40,
                        right: 10,
                        left: 10,
                        bottom: 5
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false },
                    datalabels: {
                        color: '#1f2937',
                        anchor: 'end',
                        align: 'top',
                        offset: 4,
                        clamp: true,
                        font: {
                            weight: 'bold',
                            size: 13
                        },
                        formatter: function(value, ctx) {
                            let total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return value + '\n(' + percentage + '%)';
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        suggestedMax: 5,
                        ticks: {
                            stepSize: 1,
                            precision: 0,
                            font: { size: 11 }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.04)',
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11 },
                            maxRotation: 15,
                            minRotation: 0,
                            callback: function(value) {
                                const label = this.getLabelForValue(value);
                                if (label.length > 20) {
                                    return label.substring(0, 18) + '…';
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        };

        // ---- Certificates Charts ----
        @if($view == 'certificates' || $view == 'all')
            new Chart(document.getElementById('certStatusChart'), {
                ...pieOptions,
                data: {
                    labels: ['Active', 'Warning', 'Expired'],
                    datasets: [{
                        data: [{{ $certData['active'] ?? 0 }}, {{ $certData['warning'] ?? 0 }}, {{ $certData['expired'] ?? 0 }}],
                        backgroundColor: ['#22c55e', '#eab308', '#ef4444'],
                        borderWidth: 0,
                        hoverOffset: 6
                    }]
                }
            });

            new Chart(document.getElementById('certRecommendChart'), {
                ...pieOptions,
                data: {
                    labels: ['Recommended', 'Not Recommended', 'No Recommendation'],
                    datasets: [{
                        data: [{{ $certData['recommended'] ?? 0 }}, {{ $certData['not_recommended'] ?? 0 }}, {{ $certData['no_recommendation'] ?? 0 }}],
                        backgroundColor: ['#22c55e', '#ef4444', '#9ca3af'],
                        borderWidth: 0,
                        hoverOffset: 6
                    }]
                }
            });

            new Chart(document.getElementById('certAreaChart'), {
                ...barOptions,
                data: {
                    labels: @json($certData['area_labels'] ?? []),
                    datasets: [{
                        label: 'Certificates',
                        data: @json($certData['area_data'] ?? []),
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.7)',
                            'rgba(99, 102, 241, 0.7)',
                            'rgba(139, 92, 246, 0.7)',
                            'rgba(168, 85, 247, 0.7)',
                            'rgba(192, 132, 252, 0.7)',
                            'rgba(236, 72, 153, 0.7)',
                            'rgba(244, 63, 94, 0.7)',
                            'rgba(239, 68, 68, 0.7)',
                            'rgba(251, 146, 60, 0.7)',
                            'rgba(251, 191, 36, 0.7)',
                        ],
                        borderColor: [
                            'rgba(59, 130, 246, 1)',
                            'rgba(99, 102, 241, 1)',
                            'rgba(139, 92, 246, 1)',
                            'rgba(168, 85, 247, 1)',
                            'rgba(192, 132, 252, 1)',
                            'rgba(236, 72, 153, 1)',
                            'rgba(244, 63, 94, 1)',
                            'rgba(239, 68, 68, 1)',
                            'rgba(251, 146, 60, 1)',
                            'rgba(251, 191, 36, 1)',
                        ],
                        borderWidth: 1,
                        borderRadius: 6,
                        maxBarThickness: 50
                    }]
                }
            });
        @endif

        // ---- Equipments Charts (menggunakan Total Items) ----
        @if($view == 'equipments' || $view == 'all')
            new Chart(document.getElementById('eqStatusChart'), {
                ...pieOptions,
                data: {
                    labels: ['Active', 'Warning', 'Expired'],
                    datasets: [{
                        data: [
                            {{ $eqData['total_items_active'] ?? 0 }},
                            {{ $eqData['total_items_warning'] ?? 0 }},
                            {{ $eqData['total_items_expired'] ?? 0 }}
                        ],
                        backgroundColor: ['#22c55e', '#eab308', '#ef4444'],
                        borderWidth: 0,
                        hoverOffset: 6
                    }]
                }
            });

            new Chart(document.getElementById('eqRecommendChart'), {
                ...pieOptions,
                data: {
                    labels: ['Recommended', 'Not Recommended', 'No Recommendation'],
                    datasets: [{
                        data: [
                            {{ $eqData['total_items_recommended'] ?? 0 }},
                            {{ $eqData['total_items_not_recommended'] ?? 0 }},
                            {{ $eqData['total_items_no_recommendation'] ?? 0 }}
                        ],
                        backgroundColor: ['#22c55e', '#ef4444', '#9ca3af'],
                        borderWidth: 0,
                        hoverOffset: 6
                    }]
                }
            });

            new Chart(document.getElementById('eqAreaChart'), {
                ...barOptions,
                data: {
                    labels: @json($eqData['area_labels'] ?? []),
                    datasets: [{
                        label: 'Equipments',
                        data: @json($eqData['area_data'] ?? []),
                        backgroundColor: [
                            'rgba(168, 85, 247, 0.7)',
                            'rgba(192, 132, 252, 0.7)',
                            'rgba(216, 180, 254, 0.7)',
                            'rgba(236, 72, 153, 0.7)',
                            'rgba(244, 63, 94, 0.7)',
                            'rgba(239, 68, 68, 0.7)',
                            'rgba(251, 146, 60, 0.7)',
                            'rgba(251, 191, 36, 0.7)',
                            'rgba(59, 130, 246, 0.7)',
                            'rgba(99, 102, 241, 0.7)',
                        ],
                        borderColor: [
                            'rgba(168, 85, 247, 1)',
                            'rgba(192, 132, 252, 1)',
                            'rgba(216, 180, 254, 1)',
                            'rgba(236, 72, 153, 1)',
                            'rgba(244, 63, 94, 1)',
                            'rgba(239, 68, 68, 1)',
                            'rgba(251, 146, 60, 1)',
                            'rgba(251, 191, 36, 1)',
                            'rgba(59, 130, 246, 1)',
                            'rgba(99, 102, 241, 1)',
                        ],
                        borderWidth: 1,
                        borderRadius: 6,
                        maxBarThickness: 50
                    }]
                }
            });
        @endif
    });
</script>
@endpush
@endsection