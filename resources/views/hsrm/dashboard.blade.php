@extends('layouts.hsrm-app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2" defer></script>
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
    .recommend-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid rgba(229,231,235,0.5);
        box-shadow: 0 4px 20px rgba(0,0,0,0.03), 0 1px 3px rgba(0,0,0,0.04);
        padding: 1.75rem 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        justify-content: center;
        transition: all 0.25s ease;
        min-height: 400px;
    }
    .recommend-card:hover {
        box-shadow: 0 8px 30px rgba(0,0,0,0.06), 0 2px 6px rgba(0,0,0,0.04);
        border-color: rgba(59,130,246,0.2);
    }
    .recommend-icon-wrap {
        width: 56px;
        height: 56px;
        border-radius: 9999px;
        background: #dbeafe;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        margin-bottom: 0.85rem;
    }
    .recommend-label {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #6b7280;
        margin-bottom: 0.5rem;
    }
    .recommend-value {
        font-size: 2.75rem;
        font-weight: 800;
        color: #2563eb;
        line-height: 1;
        margin-bottom: 0.6rem;
        text-decoration: none;
        display: block;
    }
    .recommend-value:hover {
        opacity: 0.85;
    }
    .recommend-desc {
        font-size: 0.85rem;
        color: #6b7280;
        max-width: 260px;
    }
    .recommend-footer {
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(229,231,235,0.6);
        font-size: 0.78rem;
        color: #9ca3af;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        width: 100%;
        justify-content: center;
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
    /* Rose / starburst chart (radius per slice mengikuti persentase) dengan leader line ke label */
    .rose-chart-wrap {
        width: 100%;
        height: 420px;
    }
    .rose-chart-wrap svg {
        width: 100%;
        height: 100%;
    }
    @media (max-width: 640px) {
        .rose-chart-wrap {
            height: 380px;
        }
    }
    /* Status chart + Recommendation card ditampilkan berdampingan dengan tinggi yang seimbang */
    .status-recommend-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem;
        align-items: stretch;
    }
    @media (min-width: 768px) {
        .status-recommend-grid {
            grid-template-columns: 1.35fr 1fr;
        }
    }
    .status-chart-card {
        display: flex;
        flex-direction: column;
        min-height: 400px;
    }
    .status-chart-card .rose-chart-wrap {
        flex: 1 1 auto;
        height: auto;
        min-height: 0;
    }
    .status-recommend-grid .recommend-card {
        min-height: 400px;
        height: 100%;
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
            @if(\App\Helpers\HsrmRole::canViewAll())
                <option value="budget" {{ $view == 'budget' ? 'selected' : '' }}>💰 Budget & Quota</option>
            @endif
            <option value="all" {{ $view == 'all' ? 'selected' : '' }}>📊 All</option>
        </select>

        {{-- Area filter for admin only --}}
        @if(\App\Helpers\HsrmRole::canViewAll())
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
@if(($view == 'budget' || $view == 'all') && \App\Helpers\HsrmRole::canViewAll())
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
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
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
                </div>
            </div>
            <div>
                <div class="text-sm font-semibold text-gray-600 mb-2">🔧 Equipments</div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
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
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-5">
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
                    <i class="fas fa-star"></i>
                </div>
            </div>
            <a href="{{ route('hsrm.certificates.index', array_filter(['rekomendasi' => 'recommended', 'area_id' => $selectedArea->id_area_kerja ?? null])) }}" class="stat-link text-2xl font-bold text-sky-600 mt-1.5">
                {{ $certData['recommended'] ?? 0 }}
            </a>
        </div>
    </div>

    @php
        $certTotalSWE = ($certData['active'] ?? 0) + ($certData['warning'] ?? 0) + ($certData['expired'] ?? 0);
        $certActivePct = $certTotalSWE > 0 ? round((($certData['active'] ?? 0) / $certTotalSWE) * 100, 1) : 0;
        $certWarningPct = $certTotalSWE > 0 ? round((($certData['warning'] ?? 0) / $certTotalSWE) * 100, 1) : 0;
        $certExpiredPct = $certTotalSWE > 0 ? round((($certData['expired'] ?? 0) / $certTotalSWE) * 100, 1) : 0;
    @endphp

    <div class="status-recommend-grid mb-5">
        <div class="chart-card status-chart-card">
            <div class="chart-title">Status</div>
            <div class="rose-chart-wrap" id="certStatusRoseWrap"></div>
        </div>

        <div class="recommend-card">
            <div class="recommend-icon-wrap">
                <i class="fas fa-star"></i>
            </div>
            <div class="recommend-label">Recommendation</div>
            <a href="{{ route('hsrm.certificates.index', array_filter(['rekomendasi' => 'recommended', 'area_id' => $selectedArea->id_area_kerja ?? null])) }}" class="recommend-value">
                {{ $certData['recommended'] ?? 0 }}
            </a>
            <div class="recommend-desc">Total certificates that need recommendation</div>
            <div class="recommend-footer">
                <i class="fas fa-info-circle"></i> Based on current certificate status
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
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-5">
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
                    <i class="fas fa-star"></i>
                </div>
            </div>
            <a href="{{ route('hsrm.equipments.index', array_filter(['rekomendasi' => 'recommended', 'area_id' => $selectedArea->id_area_kerja ?? null])) }}" class="stat-link text-2xl font-bold text-sky-600 mt-1.5">
                {{ $eqData['total_items_recommended'] ?? 0 }}
            </a>
        </div>
    </div>

    @php
        $eqTotalSWE = ($eqData['total_items_active'] ?? 0) + ($eqData['total_items_warning'] ?? 0) + ($eqData['total_items_expired'] ?? 0);
        $eqActivePct = $eqTotalSWE > 0 ? round((($eqData['total_items_active'] ?? 0) / $eqTotalSWE) * 100, 1) : 0;
        $eqWarningPct = $eqTotalSWE > 0 ? round((($eqData['total_items_warning'] ?? 0) / $eqTotalSWE) * 100, 1) : 0;
        $eqExpiredPct = $eqTotalSWE > 0 ? round((($eqData['total_items_expired'] ?? 0) / $eqTotalSWE) * 100, 1) : 0;
    @endphp

    <div class="status-recommend-grid mb-5">
        <div class="chart-card status-chart-card">
            <div class="chart-title">Status (Total Items)</div>
            <div class="rose-chart-wrap" id="eqStatusRoseWrap"></div>
        </div>

        <div class="recommend-card">
            <div class="recommend-icon-wrap">
                <i class="fas fa-star"></i>
            </div>
            <div class="recommend-label">Recommendation (Total Items)</div>
            <a href="{{ route('hsrm.equipments.index', array_filter(['rekomendasi' => 'recommended', 'area_id' => $selectedArea->id_area_kerja ?? null])) }}" class="recommend-value">
                {{ $eqData['total_items_recommended'] ?? 0 }}
            </a>
            <div class="recommend-desc">Total equipment items that need recommendation</div>
            <div class="recommend-footer">
                <i class="fas fa-info-circle"></i> Based on current equipment status
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

        // ============================================================
        // ROSE / STARBURST CHART (donut, ketebalan ring seragam, sudut = persentase)
        // Dipakai untuk chart Status Certificates & Equipments (Active/Warning/Expired)
        // ============================================================
        function renderRoseChart(containerId, slices, opts) {
            const container = document.getElementById(containerId);
            if (!container) return;

            const width = 700, height = 420;
            const cx = width * 0.4, cy = height / 2;
            // Ketebalan ring SERAGAM untuk semua slice (tidak lagi mengikuti persentase)
            const R = 150;       // radius luar (konstan)
            const holeR = 82;    // radius dalam / lubang tengah (konstan)
            const startAngle = -90; // mulai dari jam 12
            const toRad = deg => (deg * Math.PI) / 180;
            const minLabelGap = 46; // jarak vertikal minimum antar label di sisi yang sama

            let slicesSvg = '';
            let cumulative = 0;

            // --- Pass 1: hitung geometri slice + posisi label alami ---
            const labelData = slices.map((s) => {
                const sliceAngle = Math.max(s.pct, 0) * 3.6; // persen -> derajat (360/100)
                const a0 = startAngle + cumulative;
                const a1 = a0 + sliceAngle;
                const mid = (a0 + a1) / 2;
                cumulative += sliceAngle;

                const ox0 = cx + R * Math.cos(toRad(a0));
                const oy0 = cy + R * Math.sin(toRad(a0));
                const ox1 = cx + R * Math.cos(toRad(a1));
                const oy1 = cy + R * Math.sin(toRad(a1));
                const ix0 = cx + holeR * Math.cos(toRad(a0));
                const iy0 = cy + holeR * Math.sin(toRad(a0));
                const ix1 = cx + holeR * Math.cos(toRad(a1));
                const iy1 = cy + holeR * Math.sin(toRad(a1));
                const largeArc = (a1 - a0) > 180 ? 1 : 0;

                slicesSvg += `<path d="M ${ox0.toFixed(1)} ${oy0.toFixed(1)} A ${R} ${R} 0 ${largeArc} 1 ${ox1.toFixed(1)} ${oy1.toFixed(1)} L ${ix1.toFixed(1)} ${iy1.toFixed(1)} A ${holeR} ${holeR} 0 ${largeArc} 0 ${ix0.toFixed(1)} ${iy0.toFixed(1)} Z" fill="${s.color}" stroke="#ffffff" stroke-width="2"/>`;

                const tr = (R + holeR) / 2;
                const tx = cx + tr * Math.cos(toRad(mid));
                const ty = cy + tr * Math.sin(toRad(mid));
                if (sliceAngle > 12) {
                    slicesSvg += `<text x="${tx.toFixed(1)}" y="${ty.toFixed(1)}" text-anchor="middle" dominant-baseline="middle" fill="#ffffff" font-size="19" font-weight="700">${s.pct}%</text>`;
                }

                const isRight = Math.cos(toRad(mid)) >= 0;
                const anchorX = cx + R * Math.cos(toRad(mid));
                const anchorY = cy + R * Math.sin(toRad(mid));
                const bendX = cx + (R + 24) * Math.cos(toRad(mid));
                const naturalY = cy + (R + 24) * Math.sin(toRad(mid));
                const edgeX = isRight ? width - 20 : 20;

                return { s, isRight, anchorX, anchorY, bendX, naturalY, edgeX, finalY: naturalY };
            });

            // --- Pass 2: cegah label saling tumpuk di sisi yang sama (kiri/kanan) ---
            ['left', 'right'].forEach(side => {
                const group = labelData
                    .filter(d => (side === 'right') === d.isRight)
                    .sort((a, b) => a.naturalY - b.naturalY);
                for (let i = 1; i < group.length; i++) {
                    if (group[i].finalY - group[i - 1].finalY < minLabelGap) {
                        group[i].finalY = group[i - 1].finalY + minLabelGap;
                    }
                }
                // jaga agar tidak keluar dari area chart (clamp lalu dorong ulang ke atas bila perlu)
                for (let i = group.length - 1; i >= 0; i--) {
                    if (group[i].finalY > height - 16) group[i].finalY = height - 16;
                    if (i > 0 && group[i].finalY - group[i - 1].finalY < minLabelGap) {
                        group[i - 1].finalY = group[i].finalY - minLabelGap;
                    }
                }
            });

            // --- Pass 3: render leader line + teks label ---
            let labelsSvg = '';
            labelData.forEach(d => {
                const { s, isRight, anchorX, anchorY, bendX, finalY, edgeX } = d;
                const textX = edgeX;
                const anchor = isRight ? 'end' : 'start';

                labelsSvg += `<circle cx="${anchorX.toFixed(1)}" cy="${anchorY.toFixed(1)}" r="3.5" fill="${s.color}"/>`;
                labelsSvg += `<polyline points="${anchorX.toFixed(1)},${anchorY.toFixed(1)} ${bendX.toFixed(1)},${finalY.toFixed(1)} ${edgeX},${finalY.toFixed(1)}" fill="none" stroke="${s.color}" stroke-width="1.5"/>`;
                labelsSvg += `<text x="${textX}" y="${(finalY - 10).toFixed(1)}" text-anchor="${anchor}" fill="#6b7280" font-size="11.5" font-weight="600" letter-spacing="0.3">${s.label.toUpperCase()}</text>`;
                labelsSvg += `<text x="${textX}" y="${(finalY + 11).toFixed(1)}" text-anchor="${anchor}" fill="#1f2937" font-size="17" font-weight="800">${s.value}<tspan fill="#9ca3af" font-size="12" font-weight="600" dx="4">(${s.pct}%)</tspan></text>`;
            });

            const totalLabel = opts && opts.total !== undefined ? opts.total : '';

            container.innerHTML = `
                <svg viewBox="0 0 ${width} ${height}" xmlns="http://www.w3.org/2000/svg">
                    ${slicesSvg}
                    <circle cx="${cx}" cy="${cy}" r="${holeR - 4}" fill="#ffffff"/>
                    <text x="${cx}" y="${cy - 6}" text-anchor="middle" font-size="30" font-weight="800" fill="#1f2937">${totalLabel}</text>
                    <text x="${cx}" y="${cy + 20}" text-anchor="middle" font-size="11" letter-spacing="1.5" fill="#9ca3af">TOTAL</text>
                    ${labelsSvg}
                </svg>`;
        }

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
            renderRoseChart('certStatusRoseWrap', [
                { label: 'Active', value: {{ $certData['active'] ?? 0 }}, pct: {{ $certActivePct }}, color: '#16a34a' },
                { label: 'Warning', value: {{ $certData['warning'] ?? 0 }}, pct: {{ $certWarningPct }}, color: '#f59e0b' },
                { label: 'Expired', value: {{ $certData['expired'] ?? 0 }}, pct: {{ $certExpiredPct }}, color: '#dc2626' }
            ], { total: {{ $certData['total'] ?? $certTotalSWE }} });

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
            renderRoseChart('eqStatusRoseWrap', [
                { label: 'Active', value: {{ $eqData['total_items_active'] ?? 0 }}, pct: {{ $eqActivePct }}, color: '#4ade80' },
                { label: 'Warning', value: {{ $eqData['total_items_warning'] ?? 0 }}, pct: {{ $eqWarningPct }}, color: '#fbbf24' },
                { label: 'Expired', value: {{ $eqData['total_items_expired'] ?? 0 }}, pct: {{ $eqExpiredPct }}, color: '#fb7185' }
            ], { total: {{ $eqData['total_items_all'] ?? $eqTotalSWE }} });

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
        @if(($view == 'budget' || $view == 'all') && \App\Helpers\HsrmRole::canViewAll())
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