<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Smart Agriculture - AI/ML</title>
    <!-- Menggunakan Tailwind CSS langsung via CDN agar praktis -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

    <!-- Navbar -->
    <nav class="bg-green-600 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">🌱 Smart Agriculture Dashboard</h1>
    
        </div>
    </nav>

    <div class="container mx-auto p-6 mt-4">
        
        <!-- Pesan Sukses -->
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Form Tambah Lahan (Kolom Kiri) -->
            <div class="bg-white p-6 rounded-lg shadow-md col-span-1 h-fit">
                <h2 class="text-xl font-semibold mb-4 border-b pb-2">Tambah Petak Lahan</h2>
                <form action="{{ route('lahan.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Kode Petak Lahan</label>
                        <input type="text" name="kode_petak" placeholder="Misal: PLOT-A1" required class="mt-1 w-full border border-gray-300 rounded-md p-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Jenis Tanaman</label>
                        <input type="text" name="jenis_tanaman" placeholder="Misal: Padi, Jagung" required class="mt-1 w-full border border-gray-300 rounded-md p-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div class="flex gap-4 mb-3">
                        <div class="w-1/2">
                            <label class="block text-sm font-medium text-gray-700">Luas (Hektar)</label>
                            <input type="number" step="0.01" name="luas_lahan" required class="mt-1 w-full border border-gray-300 rounded-md p-2">
                        </div>
                        <div class="w-1/2">
                            <label class="block text-sm font-medium text-gray-700">Toleransi pH</label>
                            <input type="number" step="0.1" name="toleransi_ph" required class="mt-1 w-full border border-gray-300 rounded-md p-2">
                        </div>
                    </div>
                    <h3 class="font-medium text-gray-700 mt-4 mb-2 text-sm bg-gray-100 p-2 rounded">Kadar Aktual Tanah (Hasil Lab/Sensor)</h3>
                    <div class="flex gap-2 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">N Aktual</label>
                            <input type="number" step="0.01" name="n_aktual" required class="mt-1 w-full border border-gray-300 rounded-md p-2">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">P Aktual</label>
                            <input type="number" step="0.01" name="p_aktual" required class="mt-1 w-full border border-gray-300 rounded-md p-2">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">K Aktual</label>
                            <input type="number" step="0.01" name="k_aktual" required class="mt-1 w-full border border-gray-300 rounded-md p-2">
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-green-600 text-white font-bold py-2 px-4 rounded hover:bg-green-700 transition">
                        Simpan Lahan
                    </button>
                </form>
            </div>

            <!-- Daftar Lahan (Kolom Kanan) -->
            <div class="bg-white p-6 rounded-lg shadow-md col-span-2">
                <h2 class="text-xl font-semibold mb-4 border-b pb-2">Daftar Petak Lahan</h2>
                
                @if($lahans->isEmpty())
                    <div class="text-center text-gray-500 py-10">
                        Belum ada data lahan. Silakan tambahkan petak lahan baru.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead class="bg-gray-800 text-white">
                                <tr>
                                    <th class="py-2 px-4 text-left">Kode Petak</th>
                                    <th class="py-2 px-4 text-left">Tanaman</th>
                                    <th class="py-2 px-4 text-left">Luas & pH</th>
                                    <th class="py-2 px-4 text-left">N-P-K (Aktual)</th>
                                    <th class="py-2 px-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700">
                                @foreach($lahans as $lahan)
                                <tr class="border-b">
                                    <td class="py-3 px-4 font-bold text-green-700">{{ $lahan->kode_petak }}</td>
                                    <td class="py-3 px-4">{{ $lahan->jenis_tanaman }}</td>
                                    <td class="py-3 px-4">
                                        {{ $lahan->luas_lahan }} Ha <br>
                                        <span class="text-xs text-gray-500">pH: {{ $lahan->toleransi_ph }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-sm font-mono">
                                        N:{{ $lahan->n_aktual }} P:{{ $lahan->p_aktual }} K:{{ $lahan->k_aktual }}
                                    </td>
                                    <td class="py-3 px-4 flex justify-center gap-2">
                                        <!-- Tombol Lanjut ke Optimasi KNN-PSO -->
                                        <a href="{{ route('optimasi.index', $lahan->id) }}" class="bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold py-1 px-3 rounded inline-flex items-center">
                                            Kalkulasi Pupuk (AI)
                                        </a>
                                        
                                        <!-- Tombol Hapus -->
                                        <form action="{{ route('lahan.destroy', $lahan->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus lahan ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-xs font-bold py-1 px-3 rounded">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>

</body>
</html>