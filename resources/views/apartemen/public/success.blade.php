{{-- resources/views/apartemen/public/success.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-out Berhasil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-card {
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="max-w-md w-full mx-4">
        <div class="success-card bg-white rounded-2xl shadow-2xl p-8 text-center">
            
            {{-- Icon Sukses --}}
            <div class="mb-6 flex justify-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
            
            <h2 class="text-2xl font-bold text-gray-800 mb-2">
                Check-out Berhasil!
            </h2>
            
            <p class="text-gray-600 mb-4">
                Sampai jumpa kembali, 
                <span class="font-semibold text-blue-600">{{ $nama }}</span>
            </p>
            
            <div class="bg-blue-50 rounded-lg p-4 mb-6">
                <p class="text-sm text-gray-700">
                    Anda telah berhasil melakukan check-out. 
                    Terima kasih telah menggunakan fasilitas apartemen.
                </p>
            </div>
            
            <div class="space-y-3">
                <a href="{{ route('apartemen.public.logout') }}" 
                   class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition">
                    Selesai
                </a>
            </div>
            
            <p class="mt-4 text-xs text-gray-500">
                Halaman ini akan otomatis tertutup dalam <span id="countdown">10</span> detik
            </p>
        </div>
    </div>
    
    <script>
        // Hitung mundur 10 detik
        let countdown = 10;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = '{{ route("apartemen.public.logout") }}';
            }
        }, 1000);
        
        // Backup timeout (tetap 10 detik)
        setTimeout(() => {
            window.location.href = '{{ route("apartemen.public.logout") }}';
        }, 10000);
    </script>
</body>
</html>