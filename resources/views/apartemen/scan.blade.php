@extends('layouts.app-sidebar')

@section('content')
<div class="p-4 md:p-6">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 bg-gradient-to-r from-blue-600 to-blue-700">
                <h1 class="text-2xl font-bold text-white">Scan Barcode Check-in/out</h1>
                <p class="text-blue-100 mt-1">Scan kode QR untuk check-in atau check-out penghuni</p>
            </div>
            
            <div class="p-6">
                <div class="text-center mb-6">
                    <div id="reader" class="mx-auto max-w-md"></div>
                </div>
                
                <div class="text-center text-gray-500 mb-4" id="scanStatus">
                    Menunggu scan...
                </div>
                
                <div id="resultCard" class="hidden mt-4 p-4 border rounded-lg">
                    <h3 class="font-semibold text-lg mb-3">Hasil Scan</h3>
                    <div id="unitInfo" class="space-y-2 mb-4"></div>
                    <div id="penghuniList" class="space-y-2 mb-4"></div>
                    <div id="actionButtons" class="flex gap-2"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden input untuk menyimpan data sementara -->
<input type="hidden" id="currentKodeUnik">
<input type="hidden" id="currentPenghuniId">

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
const html5QrCode = new Html5Qrcode("reader");
const qrCodeSuccessCallback = (decodedText, decodedResult) => {
    // Stop scanning setelah berhasil
    html5QrCode.stop();
    
    // Proses kode
    document.getElementById('scanStatus').innerHTML = '✅ Kode terdeteksi, memproses...';
    processKode(decodedText);
};

const config = { fps: 10, qrbox: { width: 250, height: 250 } };

// Mulai kamera
html5QrCode.start({ facingMode: "environment" }, config, qrCodeSuccessCallback);

function processKode(kode) {
    fetch('/apartemen/admin/scan/process', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ kode_unik: kode })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('currentKodeUnik').value = kode;
            showUnitInfo(data);
        } else {
            alert('Gagal: ' + data.message);
            restartScan();
        }
    })
    .catch(err => {
        alert('Error: ' + err.message);
        restartScan();
    });
}

function showUnitInfo(data) {
    const resultCard = document.getElementById('resultCard');
    const unitInfo = document.getElementById('unitInfo');
    const penghuniList = document.getElementById('penghuniList');
    const actionButtons = document.getElementById('actionButtons');
    
    // Info Unit
    unitInfo.innerHTML = `
        <div class="p-3 bg-gray-50 rounded">
            <div class="font-medium">${data.unit.nama_apartemen}</div>
            <div class="text-sm text-gray-600">Unit ${data.unit.nomor_unit}</div>
            <div class="text-sm text-gray-600">Periode: ${data.periode.mulai} - ${data.periode.selesai}</div>
            <div class="mt-2">
                <span class="inline-block px-2 py-1 text-xs rounded-full ${data.sudah_checkin ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                    Check-in: ${data.sudah_checkin ? '✓ Sudah' : 'Belum'}
                </span>
                <span class="inline-block px-2 py-1 text-xs rounded-full ml-2 ${data.sudah_checkout ? 'bg-red-100 text-red-800' : 'bg-gray-100'}">
                    Check-out: ${data.sudah_checkout ? '✓ Sudah' : 'Belum'}
                </span>
            </div>
        </div>
    `;
    
    // Penghuni
    penghuniList.innerHTML = '<h4 class="font-medium">Pilih Penghuni:</h4>';
    data.penghuni.forEach(p => {
        penghuniList.innerHTML += `
            <label class="flex items-center p-2 border rounded hover:bg-gray-50 cursor-pointer">
                <input type="radio" name="penghuni" value="${p.id}" class="mr-3 penghuni-radio">
                <div>
                    <div class="font-medium">${p.nama}</div>
                    <div class="text-sm text-gray-500">${p.id_karyawan}</div>
                </div>
            </label>
        `;
    });
    
    // Action Buttons
    actionButtons.innerHTML = '';
    if (data.can_checkin) {
        actionButtons.innerHTML += `
            <button onclick="doCheckin()" 
                    class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                Check-in
            </button>
        `;
    }
    if (data.can_checkout) {
        actionButtons.innerHTML += `
            <button onclick="doCheckout()" 
                    class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
                Check-out
            </button>
        `;
    }
    actionButtons.innerHTML += `
        <button onclick="restartScan()" 
                class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
            Scan Lagi
        </button>
    `;
    
    resultCard.classList.remove('hidden');
    document.getElementById('scanStatus').innerHTML = '';
}

function doCheckin() {
    const penghuniId = document.querySelector('input[name="penghuni"]:checked')?.value;
    if (!penghuniId) {
        alert('Pilih penghuni terlebih dahulu');
        return;
    }
    
    fetch('/apartemen/admin/scan/checkin', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            kode_unik: document.getElementById('currentKodeUnik').value,
            penghuni_id: penghuniId
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message + ' jam ' + data.waktu);
            restartScan();
        } else {
            alert('❌ ' + data.message);
        }
    });
}

function doCheckout() {
    const penghuniId = document.querySelector('input[name="penghuni"]:checked')?.value;
    if (!penghuniId) {
        alert('Pilih penghuni terlebih dahulu');
        return;
    }
    
    fetch('/apartemen/admin/scan/checkout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            kode_unik: document.getElementById('currentKodeUnik').value,
            penghuni_id: penghuniId
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message + ' jam ' + data.waktu);
            restartScan();
        } else {
            alert('❌ ' + data.message);
        }
    });
}

function restartScan() {
    document.getElementById('resultCard').classList.add('hidden');
    document.getElementById('scanStatus').innerHTML = 'Menunggu scan...';
    html5QrCode.start({ facingMode: "environment" }, config, qrCodeSuccessCallback);
}
</script>
@endsection