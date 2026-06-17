<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantau Tumbuh Kembang Anak</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white p-8 rounded-xl shadow-lg max-w-md w-full">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-blue-600">e-Posyandu</h1>
            <p class="text-gray-500 text-sm">Cek Catatan Tumbuh Kembang Balita Anda</p>
        </div>

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('pantau.cari') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Nama Balita</label>
                <input type="text" name="nama" required placeholder="Contoh: Kailash Asher" 
                       class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500">
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Tanggal Lahir</label>
                <input type="date" name="tgl_lahir" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500">
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                Cari Data Anak
            </button>
        </form>
    </div>

</body>
</html>