<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Generator Template Hotspot') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">Pengaturan Template Anda</h3>
                    
                    @if (session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif
                    
                    @if (session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    <form action="{{ route('template.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="business_name" class="block text-sm font-medium text-gray-700">Nama Usaha / Judul Hotspot</label>
                            <input type="text" name="business_name" id="business_name" value="{{ old('business_name', $config->business_name) }}" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            @error('business_name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="running_text" class="block text-sm font-medium text-gray-700">Teks Berjalan (Marquee)</label>
                            <input type="text" name="running_text" id="running_text" value="{{ old('running_text', $config->running_text) }}" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Selamat datang di layanan WiFi kami...">
                            @error('running_text')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Logo Saat Ini</label>
                            @if($config->logo_path)
                                <img src="{{ asset('storage/' . $config->logo_path) }}" alt="Logo" class="h-24 object-contain bg-gray-100 p-2 rounded mb-2">
                            @else
                                <p class="text-sm text-gray-500 italic mb-2">Belum ada logo yang diunggah. Logo default akan digunakan.</p>
                            @endif
                            
                            <label for="logo" class="block text-sm font-medium text-gray-700">Unggah Logo Baru (PNG/JPG, Max 2MB)</label>
                            <input type="file" name="logo" id="logo" accept="image/png, image/jpeg" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            @error('logo')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Simpan Konfigurasi
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-indigo-50 border border-indigo-100 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-indigo-900 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold mb-1">Siap untuk digunakan?</h3>
                        <p class="text-sm">Unduh template hotspot Anda yang sudah dikonfigurasi dan masukkan ke dalam router MikroTik.</p>
                    </div>
                    <div>
                        <a href="{{ route('template.download') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-bold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Download Template (.zip)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
