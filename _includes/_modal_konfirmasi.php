<div 
    x-data 
    x-show="$store.modal.isOpen"
    x-on:keydown.escape.window="$store.modal.isOpen = false"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-white/75 backdrop-blur-sm"
    style="display: none;" 
    x-cloak>
    
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        
        <div class="p-6">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900">Hapus Transaksi</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
            <button id="tombol-batal" type="button" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none">
                Batal
            </button>
            <a id="tombol-konfirmasi-hapus" href="#" class="px-4 py-2 bg-red-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-red-700 focus:outline-none">
                Ya, Hapus
            </a>
        </div>
        
    </div>
</div>