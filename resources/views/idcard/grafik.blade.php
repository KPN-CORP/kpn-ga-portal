@extends('layouts.app-sidebar-card')
@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-2xl font-semibold mb-4">Grafik Request ID Card per Bisnis Unit</h2>
    <canvas id="idcardChart" height="150"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('idcardChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [
                { label: 'Pending', data: @json($pendingData), backgroundColor: 'rgba(255,206,86,0.6)', borderColor: 'rgb(255,206,86)', borderWidth: 1 },
                { label: 'Approved', data: @json($approvedData), backgroundColor: 'rgba(75,192,192,0.6)', borderColor: 'rgb(75,192,192)', borderWidth: 1 },
                { label: 'Rejected', data: @json($rejectedData), backgroundColor: 'rgba(255,99,132,0.6)', borderColor: 'rgb(255,99,132)', borderWidth: 1 }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                title: { display: true, text: 'Distribusi Request ID Card per BU' }
            },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>
@endsection