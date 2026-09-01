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
        min-height: 400px;
    }
    .chart-container.budget-chart {
        min-height: 550px;
    }
    .chart-container.quota-chart {
        min-height: 450px;
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
    .status-revision { background: #fee2e2; color: #dc2626; }
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
    .area-input {
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        padding: 0.4rem 0.75rem;
        font-size: 0.875rem;
        width: 180px;
        transition: all 0.15s;
    }
    .area-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59,130,246,0.2);
    }
    .filter-btn {
        background: #f3f4f6;
        padding: 0.4rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        border: 1px solid #d1d5db;
        cursor: pointer;
        transition: all 0.15s;
    }
    .filter-btn:hover {
        background: #e5e7eb;
    }
    .filter-btn-clear {
        color: #6b7280;
        font-size: 0.875rem;
        text-decoration: none;
        padding: 0.4rem 0.5rem;
    }
    .filter-btn-clear:hover {
        color: #1f2937;
    }
    @media (max-width: 640px) {
        .chart-container.budget-chart {
            min-height: 400px;
        }
        .chart-container.quota-chart {
            min-height: 350px;
        }
        .chart-container {
            min-height: 300px;
        }
        .area-input {
            width: 140px;
        }
    }
    .input-error {
        border-color: #ef4444 !important;
    }
    .area-error-text {
        color: #ef4444;
        font-size: 0.75rem;
        margin-top: 0.25rem;
        display: none;
    }
    .area-error-text.show {
        display: block;
    }
</style>
@endpush

@section('content')
{{-- Filter --}}
<div class="flex flex-wrap justify-between items-center mb-6 gap-3">
    <div class="flex items-center space-x-3 flex-wrap gap-2">
        <label class="text-sm font-medium text-gray-700">View:</label>
        <select id="viewFilter" class="border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 bg-white shadow-sm">
            <option value="certificates" {{ $view == 'certificates' ? 'selected' : '' }}>📄 Certificates</option>
            <option value="equipments" {{ $view == 'equipments' ? 'selected' : '' }}>🔧 Equipments</option>
            @if(session('hsrm_role') === 'admin')
                <option value="budget" {{ $view == 'budget' ? 'selected' : '' }}>💰 Budget & Quota</option>
            @endif
            <option value="all" {{ $view == 'all' ? 'selected' : '' }}>📊 All</option>
        </select>

        {{-- Area filter for admin only --}}
        @if(session('hsrm_role') === 'admin')
            <form method="GET" action="{{ route('hsrm.dashboard') }}" id="area-filter-form" class="flex items-center gap-2">
                <input type="hidden" name="view" value="{{ $view }}">
                <div class="relative">
                    <input type="text"
                           id="area_name"
                           name="area_name"
                           list="area-list"
                           value="{{ $selectedArea ? $selectedArea->nama_area : '' }}"
                           class="area-input @error('area_id') input-error @enderror"
                           placeholder="Filter area..."
                           autocomplete="off">
                    <input type="hidden" name="area_id" id="area_id" value="{{ $selectedArea ? $selectedArea->id_area_kerja : '' }}">
                    <datalist id="area-list">
                        @foreach($areas as $area)
                            <option value="{{ $area->nama_area }}" data-id="{{ $area->id_area_kerja }}">
                        @endforeach
                    </datalist>
                    <div id="area-error" class="area-error-text">Area tidak valid. Pilih dari daftar.</div>
                </div>
                <button type="submit" class="filter-btn">Filter</button>
                @if($selectedArea)
                    <a href="{{ route('hsrm.dashboard', ['view' => $view]) }}" class="filter-btn-clear">Clear</a>
                @endif
            </form>
        @endif
    </div>
    <div class="text-sm text-gray-500 bg-gray-50/80 px-4 py-2 rounded-full border border-gray-200/60">
        @if($view == 'certificates')
            Showing <strong>Certificates</strong> only
        @elseif($view == 'equipments')
            Showing <strong>Equipments</strong> only
        @elseif($view == 'budget')
            Showing <strong>Budget & Quota</strong> summary
        @else
            Showing <strong>All</strong> data
        @endif
        @if($selectedArea)
            <span class="ml-2">| Area: <strong>{{ $selectedArea->nama_area }}</strong></span>
        @endif
    </div>
</div>

{{-- ============================================================ --}}
{{-- BUDGET & QUOTA VIEW (Admin only) --}}
{{-- ============================================================ --}}
@if(($view == 'budget' || $view == 'all') && session('hsrm_role') === 'admin')
    <div class="mb-8">
        <div class="flex items-center mb-4">
            <div class="section-divider bg-green-500"></div>
            <h3 class="section-title">💰 Budget & Quota Analytics</h3>
        </div>

        {{-- Overview Stats: Active/Warning/Expired/Recommendation (Certificates + Equipments) --}}
        @if(isset($certData) || isset($eqData))
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
            <div>
                <div class="text-sm font-semibold text-gray-600 mb-2">📄 Certificates</div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="stat-card">
                        <div class="text-sm text-gray-500">Active</div>
                        <div class="text-xl font-bold text-green-600 mt-1">{{ $certData['active'] ?? 0 }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="text-sm text-gray-500">Warning</div>
                        <div class="text-xl font-bold text-yellow-600 mt-1">{{ $certData['warning'] ?? 0 }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="text-sm text-gray-500">Expired</div>
                        <div class="text-xl font-bold text-red-600 mt-1">{{ $certData['expired'] ?? 0 }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="text-sm text-gray-500">Recommendation</div>
                        <div class="text-xl font-bold text-sky-600 mt-1">{{ $certData['recommended'] ?? 0 }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="text-sm text-gray-500">Not Recommendation</div>
                        <div class="text-xl font-bold text-rose-600 mt-1">{{ $certData['not_recommended'] ?? 0 }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="text-sm text-gray-500">Valid</div>
                        <div class="text-xl font-bold text-violet-600 mt-1">{{ $certData['valid'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
            <div>
                <div class="text-sm font-semibold text-gray-600 mb-2">🔧 Equipments</div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="stat-card">
                        <div class="text-sm text-gray-500">Active</div>
                        <div class="text-xl font-bold text-green-600 mt-1">{{ $eqData['total_items_active'] ?? 0 }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="text-sm text-gray-500">Warning</div>
                        <div class="text-xl font-bold text-yellow-600 mt-1">{{ $eqData['total_items_warning'] ?? 0 }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="text-sm text-gray-500">Expired</div>
                        <div class="text-xl font-bold text-red-600 mt-1">{{ $eqData['total_items_expired'] ?? 0 }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="text-sm text-gray-500">Recommendation</div>
                        <div class="text-xl font-bold text-sky-600 mt-1">{{ $eqData['total_items_recommended'] ?? 0 }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="text-sm text-gray-500">Not Recommendation</div>
                        <div class="text-xl font-bold text-rose-600 mt-1">{{ $eqData['total_items_not_recommended'] ?? 0 }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="text-sm text-gray-500">Valid</div>
                        <div class="text-xl font-bold text-violet-600 mt-1">{{ $eqData['total_items_valid'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Grafik Budget per Area --}}
        @if(isset($budgetData) && $budgetData->count() > 0)
        <div class="chart-card mb-5">
            <div class="chart-title">Budget per Area (Rp)</div>
            <div class="chart-container budget-chart" style="height:550px;">
                <canvas id="budgetAreaChart"></canvas>
            </div>
        </div>
        @else
        <div class="bg-gray-50 p-8 text-center text-gray-500 rounded-xl border mb-5">
            <i class="fas fa-info-circle mr-2"></i> Tidak ada data budget untuk area ini.
        </div>
        @endif

        {{-- Grafik Quota Fulfillment --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="chart-card">
                <div class="chart-title">Certificates Quota vs Active</div>
                <div class="chart-container quota-chart" style="height:450px;">
                    <canvas id="quotaCertFulfillmentChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-title">Equipments Quota vs Active</div>
                <div class="chart-container quota-chart" style="height:450px;">
                    <canvas id="quotaEqFulfillmentChart"></canvas>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- ============================================================ --}}
{{-- CERTIFICATES VIEW --}}
{{-- ============================================================ --}}
@if($view == 'certificates' || $view == 'all')
<div class="mb-8">
    <div class="flex items-center mb-4">
        <div class="section-divider bg-blue-500"></div>
        <h3 class="section-title">📄 Certificates Analytics</h3>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-5">
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">Total Certificates</div>
                <div class="stat-icon bg-blue-50 text-blue-500">
                    <i class="fas fa-file-alt"></i>
                </div>
            </div>
            <a href="{{ route('hsrm.certificates.filter', array_filter(['filter' => 'total', 'area_id' => $selectedArea->id_area_kerja ?? null])) }}" class="stat-link text-2xl font-bold mt-1.5">
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
            <a href="{{ route('hsrm.certificates.filter', array_filter(['filter' => 'active', 'area_id' => $selectedArea->id_area_kerja ?? null])) }}" class="stat-link text-2xl font-bold text-green-600 mt-1.5">
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
            <a href="{{ route('hsrm.certificates.filter', array_filter(['filter' => 'warning', 'area_id' => $selectedArea->id_area_kerja ?? null])) }}" class="stat-link text-2xl font-bold text-yellow-600 mt-1.5">
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
            <a href="{{ route('hsrm.certificates.filter', array_filter(['filter' => 'expired', 'area_id' => $selectedArea->id_area_kerja ?? null])) }}" class="stat-link text-2xl font-bold text-red-600 mt-1.5">
                {{ $certData['expired'] ?? 0 }}
            </a>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">Recommendation</div>
                <div class="stat-icon bg-sky-50 text-sky-500">
                    <i class="fas fa-thumbs-up"></i>
                </div>
            </div>
            <a href="{{ route('hsrm.certificates.index', array_filter(['rekomendasi' => 'recommended', 'area_id' => $selectedArea->id_area_kerja ?? null])) }}" class="stat-link text-2xl font-bold text-sky-600 mt-1.5">
                {{ $certData['recommended'] ?? 0 }}
            </a>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">Not Recommendation</div>
                <div class="stat-icon bg-rose-50 text-rose-500">
                    <i class="fas fa-thumbs-down"></i>
                </div>
            </div>
            <a href="{{ route('hsrm.certificates.index', array_filter(['rekomendasi' => 'not_recommended', 'area_id' => $selectedArea->id_area_kerja ?? null])) }}" class="stat-link text-2xl font-bold text-rose-600 mt-1.5">
                {{ $certData['not_recommended'] ?? 0 }}
            </a>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">Valid</div>
                <div class="stat-icon bg-violet-50 text-violet-500">
                    <i class="fas fa-certificate"></i>
                </div>
            </div>
            <a href="{{ route('hsrm.certificates.index', array_filter(['rekomendasi' => 'valid', 'area_id' => $selectedArea->id_area_kerja ?? null])) }}" class="stat-link text-2xl font-bold text-violet-600 mt-1.5">
                {{ $certData['valid'] ?? 0 }}
            </a>
        </div>
    </div>


    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
        <div class="chart-card">
            <div class="chart-title">Status</div>
            <div class="chart-container" style="height:400px;">
                <canvas id="certStatusChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <div class="chart-title">Recommendation</div>
            <div class="chart-container" style="height:400px;">
                <canvas id="certRecommendChart"></canvas>
            </div>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-title">By Area</div>
        <div class="chart-container" style="height:500px;">
            <canvas id="certAreaChart"></canvas>
        </div>
    </div>
</div>
@endif

{{-- ============================================================ --}}
{{-- EQUIPMENTS VIEW --}}
{{-- ============================================================ --}}
@if($view == 'equipments' || $view == 'all')
<div class="mb-8">
    <div class="flex items-center mb-4">
        <div class="section-divider bg-purple-500"></div>
        <h3 class="section-title">🔧 Equipments Analytics</h3>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-5">
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">Total Equipments</div>
                <div class="stat-icon bg-purple-50 text-purple-500">
                    <i class="fas fa-fire-extinguisher"></i>
                </div>
            </div>
            <a href="{{ route('hsrm.equipments.filter', array_filter(['filter' => 'total', 'area_id' => $selectedArea->id_area_kerja ?? null])) }}" class="stat-link text-2xl font-bold mt-1.5">
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
            <a href="{{ route('hsrm.equipments.filter', array_filter(['filter' => 'active', 'area_id' => $selectedArea->id_area_kerja ?? null])) }}" class="stat-link text-2xl font-bold text-green-600 mt-1.5">
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
            <a href="{{ route('hsrm.equipments.filter', array_filter(['filter' => 'warning', 'area_id' => $selectedArea->id_area_kerja ?? null])) }}" class="stat-link text-2xl font-bold text-yellow-600 mt-1.5">
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
            <a href="{{ route('hsrm.equipments.filter', array_filter(['filter' => 'expired', 'area_id' => $selectedArea->id_area_kerja ?? null])) }}" class="stat-link text-2xl font-bold text-red-600 mt-1.5">
                {{ $eqData['total_items_expired'] ?? 0 }}
            </a>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">Recommendation</div>
                <div class="stat-icon bg-sky-50 text-sky-500">
                    <i class="fas fa-thumbs-up"></i>
                </div>
            </div>
            <a href="{{ route('hsrm.equipments.index', array_filter(['rekomendasi' => 'recommended', 'area_id' => $selectedArea->id_area_kerja ?? null])) }}" class="stat-link text-2xl font-bold text-sky-600 mt-1.5">
                {{ $eqData['total_items_recommended'] ?? 0 }}
            </a>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">Not Recommendation</div>
                <div class="stat-icon bg-rose-50 text-rose-500">
                    <i class="fas fa-thumbs-down"></i>
                </div>
            </div>
            <a href="{{ route('hsrm.equipments.index', array_filter(['rekomendasi' => 'not_recommended', 'area_id' => $selectedArea->id_area_kerja ?? null])) }}" class="stat-link text-2xl font-bold text-rose-600 mt-1.5">
                {{ $eqData['total_items_not_recommended'] ?? 0 }}
            </a>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">Valid</div>
                <div class="stat-icon bg-violet-50 text-violet-500">
                    <i class="fas fa-certificate"></i>
                </div>
            </div>
            <a href="{{ route('hsrm.equipments.index', array_filter(['rekomendasi' => 'valid', 'area_id' => $selectedArea->id_area_kerja ?? null])) }}" class="stat-link text-2xl font-bold text-violet-600 mt-1.5">
                {{ $eqData['total_items_valid'] ?? 0 }}
            </a>
        </div>
    </div>


    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
        <div class="chart-card">
            <div class="chart-title">Status (Total Items)</div>
            <div class="chart-container" style="height:400px;">
                <canvas id="eqStatusChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <div class="chart-title">Recommendation (Total Items)</div>
            <div class="chart-container" style="height:400px;">
                <canvas id="eqRecommendChart"></canvas>
            </div>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-title">By Area</div>
        <div class="chart-container" style="height:500px;">
            <canvas id="eqAreaChart"></canvas>
        </div>
    </div>
</div>
@endif

{{-- ============================================================ --}}
{{-- RECENT ITEMS --}}
{{-- ============================================================ --}}
@if(($view == 'certificates' || $view == 'all') && !($view == 'budget'))
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
                    <span class="text-xs text-gray-400 ml-2">({{ $cert->area->nama_area ?? '-' }})</span>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="status-badge 
                        @if($cert->status_verif == 'pending') status-pending
                        @elseif($cert->status_verif == 'verified') status-verified
                        @else status-revision @endif">
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

@if(($view == 'equipments' || $view == 'all') && !($view == 'budget'))
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
                    <span class="text-xs text-gray-400 ml-2">({{ $eq->area->nama_area ?? '-' }})</span>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="status-badge 
                        @if($eq->status_verif == 'pending') status-pending
                        @elseif($eq->status_verif == 'verified') status-verified
                        @else status-revision @endif">
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
        // ============================================================
        // VIEW FILTER
        // ============================================================
        document.getElementById('viewFilter').addEventListener('change', function() {
            const view = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('view', view);
            // Hapus area_id jika view berubah ke budget (tapi tetap pertahankan jika ada)
            window.location.href = url.toString();
        });

        // ============================================================
        // AREA VALIDATION (Datalist)
        // ============================================================
        const areaInput = document.getElementById('area_name');
        const areaIdHidden = document.getElementById('area_id');
        const datalist = document.getElementById('area-list');
        const errorDiv = document.getElementById('area-error');
        const form = document.getElementById('area-filter-form');

        if (areaInput) {
            function validateArea() {
                const typedValue = areaInput.value.trim();
                const options = datalist.options;
                let found = false;
                let foundId = null;

                for (let opt of options) {
                    if (opt.value === typedValue) {
                        found = true;
                        foundId = opt.dataset.id;
                        break;
                    }
                }

                if (found && foundId) {
                    areaIdHidden.value = foundId;
                    areaInput.classList.remove('input-error');
                    errorDiv.classList.remove('show');
                    return true;
                } else {
                    if (typedValue === '') {
                        areaIdHidden.value = '';
                        areaInput.classList.remove('input-error');
                        errorDiv.classList.remove('show');
                        return true;
                    }
                    areaIdHidden.value = '';
                    areaInput.classList.add('input-error');
                    errorDiv.classList.add('show');
                    return false;
                }
            }

            areaInput.addEventListener('input', validateArea);
            areaInput.addEventListener('blur', validateArea);

            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!validateArea()) {
                        e.preventDefault();
                        areaInput.focus();
                        alert('Silakan pilih area dari daftar yang tersedia.');
                    }
                });
            }
        }

        // ============================================================
        // REGISTER CHART PLUGIN
        // ============================================================
        Chart.register(ChartDataLabels);

        // --- Pie chart config ---
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

        // --- Bar chart config ---
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

        // ============================================================
        // CERTIFICATES CHARTS
        // ============================================================
        @if($view == 'certificates' || $view == 'all')
            const certStatusEl = document.getElementById('certStatusChart');
            if (certStatusEl) {
                new Chart(certStatusEl, {
                    ...pieOptions,
                    data: {
                        labels: ['Active', 'Warning', 'Expired'],
                        datasets: [{
                            data: [
                                {{ $certData['active'] ?? 0 }},
                                {{ $certData['warning'] ?? 0 }},
                                {{ $certData['expired'] ?? 0 }}
                            ],
                            backgroundColor: ['#16a34a', '#f59e0b', '#dc2626'],
                            borderWidth: 0,
                            hoverOffset: 6
                        }]
                    }
                });
            }

            const certRecommendEl = document.getElementById('certRecommendChart');
            if (certRecommendEl) {
                new Chart(certRecommendEl, {
                    ...pieOptions,
                    data: {
                        labels: ['Recommended', 'Not Recommended', 'Valid'],
                        datasets: [{
                            data: [
                                {{ $certData['recommended'] ?? 0 }},
                                {{ $certData['not_recommended'] ?? 0 }},
                                {{ $certData['valid'] ?? 0 }}
                            ],
                            backgroundColor: ['#0ea5e9', '#f43f5e', '#8b5cf6'],
                            borderWidth: 0,
                            hoverOffset: 6
                        }]
                    }
                });
            }

            const certAreaEl = document.getElementById('certAreaChart');
            if (certAreaEl && @json($certData['area_labels'] ?? []).length > 0) {
                new Chart(certAreaEl, {
                    ...barOptions,
                    data: {
                        labels: @json($certData['area_labels'] ?? []),
                        datasets: [{
                            label: 'Certificates',
                            data: @json($certData['area_data'] ?? []),
                            backgroundColor: [
                                'rgba(37, 99, 235, 0.75)',
                                'rgba(29, 78, 216, 0.75)',
                                'rgba(30, 64, 175, 0.75)',
                                'rgba(14, 116, 144, 0.75)',
                                'rgba(8, 145, 178, 0.75)',
                                'rgba(6, 182, 212, 0.75)',
                                'rgba(2, 132, 199, 0.75)',
                                'rgba(3, 105, 161, 0.75)',
                                'rgba(12, 74, 110, 0.75)',
                                'rgba(56, 189, 248, 0.75)',
                            ],
                            borderColor: [
                                'rgba(37, 99, 235, 1)',
                                'rgba(29, 78, 216, 1)',
                                'rgba(30, 64, 175, 1)',
                                'rgba(14, 116, 144, 1)',
                                'rgba(8, 145, 178, 1)',
                                'rgba(6, 182, 212, 1)',
                                'rgba(2, 132, 199, 1)',
                                'rgba(3, 105, 161, 1)',
                                'rgba(12, 74, 110, 1)',
                                'rgba(56, 189, 248, 1)',
                            ],
                            borderWidth: 1,
                            borderRadius: 6,
                            maxBarThickness: 50
                        }]
                    }
                });
            }
        @endif

        // ============================================================
        // EQUIPMENTS CHARTS
        // ============================================================
        @if($view == 'equipments' || $view == 'all')
            const eqStatusEl = document.getElementById('eqStatusChart');
            if (eqStatusEl) {
                new Chart(eqStatusEl, {
                    ...pieOptions,
                    data: {
                        labels: ['Active', 'Warning', 'Expired'],
                        datasets: [{
                            data: [
                                {{ $eqData['total_items_active'] ?? 0 }},
                                {{ $eqData['total_items_warning'] ?? 0 }},
                                {{ $eqData['total_items_expired'] ?? 0 }}
                            ],
                            backgroundColor: ['#4ade80', '#fbbf24', '#fb7185'],
                            borderWidth: 0,
                            hoverOffset: 6
                        }]
                    }
                });
            }

            const eqRecommendEl = document.getElementById('eqRecommendChart');
            if (eqRecommendEl) {
                new Chart(eqRecommendEl, {
                    ...pieOptions,
                    data: {
                        labels: ['Recommended', 'Not Recommended', 'Valid'],
                        datasets: [{
                            data: [
                                {{ $eqData['total_items_recommended'] ?? 0 }},
                                {{ $eqData['total_items_not_recommended'] ?? 0 }},
                                {{ $eqData['total_items_valid'] ?? 0 }}
                            ],
                            backgroundColor: ['#06b6d4', '#e11d48', '#a855f7'],
                            borderWidth: 0,
                            hoverOffset: 6
                        }]
                    }
                });
            }

            const eqAreaEl = document.getElementById('eqAreaChart');
            if (eqAreaEl && @json($eqData['area_labels'] ?? []).length > 0) {
                new Chart(eqAreaEl, {
                    ...barOptions,
                    data: {
                        labels: @json($eqData['area_labels'] ?? []),
                        datasets: [{
                            label: 'Equipments',
                            data: @json($eqData['area_data'] ?? []),
                            backgroundColor: [
                                'rgba(234, 88, 12, 0.75)',
                                'rgba(217, 119, 6, 0.75)',
                                'rgba(202, 138, 4, 0.75)',
                                'rgba(180, 83, 9, 0.75)',
                                'rgba(154, 52, 18, 0.75)',
                                'rgba(245, 158, 11, 0.75)',
                                'rgba(194, 65, 12, 0.75)',
                                'rgba(120, 53, 15, 0.75)',
                                'rgba(249, 115, 22, 0.75)',
                                'rgba(161, 98, 7, 0.75)',
                            ],
                            borderColor: [
                                'rgba(234, 88, 12, 1)',
                                'rgba(217, 119, 6, 1)',
                                'rgba(202, 138, 4, 1)',
                                'rgba(180, 83, 9, 1)',
                                'rgba(154, 52, 18, 1)',
                                'rgba(245, 158, 11, 1)',
                                'rgba(194, 65, 12, 1)',
                                'rgba(120, 53, 15, 1)',
                                'rgba(249, 115, 22, 1)',
                                'rgba(161, 98, 7, 1)',
                            ],
                            borderWidth: 1,
                            borderRadius: 6,
                            maxBarThickness: 50
                        }]
                    }
                });
            }
        @endif

        // ============================================================
        // BUDGET & QUOTA CHARTS (Admin only)
        // ============================================================
        @if(($view == 'budget' || $view == 'all') && session('hsrm_role') === 'admin')
            // --- Grafik Budget ---
            @if(isset($budgetData) && $budgetData->count() > 0)
                const budgetEl = document.getElementById('budgetAreaChart');
                if (budgetEl) {
                    const budgetItems = @json($budgetData->values());
                    const budgetLabels = budgetItems.map(item => item.area_name);
                    const budgetValues = budgetItems.map(item => item.total_budget);

                    new Chart(budgetEl, {
                        type: 'bar',
                        data: {
                            labels: budgetLabels,
                            datasets: [{
                                label: 'Budget (Rp)',
                                data: budgetValues,
                                backgroundColor: 'rgba(20, 184, 166, 0.75)',
                                borderColor: 'rgba(20, 184, 166, 1)',
                                borderWidth: 1,
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    top: 50,
                                    bottom: 10,
                                    left: 10,
                                    right: 10
                                }
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: { enabled: false },
                                datalabels: {
                                    color: '#1f2937',
                                    anchor: 'end',
                                    align: 'top',
                                    offset: 6,
                                    font: { weight: 'bold', size: 12 },
                                    formatter: function(value) {
                                        return 'Rp ' + value.toLocaleString('id-ID');
                                    },
                                    clip: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return 'Rp ' + value.toLocaleString('id-ID');
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            @endif

            // --- Grafik Certificate Quota ---
            @if(isset($certQuotaData) && $certQuotaData->count() > 0)
                const certQuotaEl = document.getElementById('quotaCertFulfillmentChart');
                if (certQuotaEl) {
                    const certQuotaItems = @json($certQuotaData->values());
                    const certLabels = certQuotaItems.map(item => item.area_name);
                    const certQuota = certQuotaItems.map(item => item.certificate_quota);
                    const certActive = certQuotaItems.map(item => item.certificate_active);

                    new Chart(certQuotaEl, {
                        type: 'bar',
                        data: {
                            labels: certLabels,
                            datasets: [
                                {
                                    label: 'Quota',
                                    data: certQuota,
                                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                                    borderColor: 'rgba(59, 130, 246, 1)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Active',
                                    data: certActive,
                                    backgroundColor: 'rgba(22, 163, 74, 0.75)',
                                    borderColor: 'rgba(22, 163, 74, 1)',
                                    borderWidth: 1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    top: 40
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: { usePointStyle: true, padding: 12 }
                                },
                                tooltip: { enabled: false },
                                datalabels: {
                                    anchor: 'end',
                                    align: 'top',
                                    offset: 4,
                                    font: { weight: 'bold', size: 11 },
                                    color: '#1f2937',
                                    formatter: function(value) {
                                        return value;
                                    },
                                    clip: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { stepSize: 1, precision: 0 }
                                }
                            }
                        }
                    });
                }
            @endif

            // --- Grafik Equipment Quota ---
            @if(isset($eqQuotaData) && $eqQuotaData->count() > 0)
                const eqQuotaEl = document.getElementById('quotaEqFulfillmentChart');
                if (eqQuotaEl) {
                    const eqQuotaItems = @json($eqQuotaData->values());
                    const eqLabels = eqQuotaItems.map(item => item.area_name);
                    const eqQuota = eqQuotaItems.map(item => item.equipment_quota);
                    const eqActive = eqQuotaItems.map(item => item.equipment_active);

                    new Chart(eqQuotaEl, {
                        type: 'bar',
                        data: {
                            labels: eqLabels,
                            datasets: [
                                {
                                    label: 'Quota (items)',
                                    data: eqQuota,
                                    backgroundColor: 'rgba(139, 92, 246, 0.7)',
                                    borderColor: 'rgba(139, 92, 246, 1)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Active (items)',
                                    data: eqActive,
                                    backgroundColor: 'rgba(132, 204, 22, 0.75)',
                                    borderColor: 'rgba(132, 204, 22, 1)',
                                    borderWidth: 1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    top: 40
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: { usePointStyle: true, padding: 12 }
                                },
                                tooltip: { enabled: false },
                                datalabels: {
                                    anchor: 'end',
                                    align: 'top',
                                    offset: 4,
                                    font: { weight: 'bold', size: 11 },
                                    color: '#1f2937',
                                    formatter: function(value) {
                                        return value;
                                    },
                                    clip: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { stepSize: 1, precision: 0 }
                                }
                            }
                        }
                    });
                }
            @endif
        @endif
    });
</script>
@endpush
@endsection