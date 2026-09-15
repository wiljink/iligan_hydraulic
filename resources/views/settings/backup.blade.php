<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Backup &amp; Restore</h2>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="font-semibold mb-2">Create Backup</h3>
            <p class="text-sm text-gray-500 mb-4">Creates a downloadable .zip containing a database dump (and any product images).</p>
            <form method="POST" action="{{ route('backup.run') }}" class="space-y-3">
                @csrf
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="only_db" value="1" checked class="rounded border-gray-300">
                    Database only (faster, recommended)
                </label>
                <x-primary-button>Run Backup Now</x-primary-button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6" x-data="{ restoreOpen: false, mode: 'existing', target: '' }">
            <h3 class="font-semibold mb-3">Available Backups</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2">File</th>
                            <th class="py-2">Date</th>
                            <th class="py-2">Size</th>
                            <th class="py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($backups as $backup)
                            <tr class="border-b last:border-0">
                                <td class="py-2 font-mono text-xs">{{ $backup['filename'] }}</td>
                                <td class="py-2">{{ $backup['date']->format('M d, Y g:ia') }}</td>
                                <td class="py-2">{{ $backup['size_human'] }}</td>
                                <td class="py-2 text-right whitespace-nowrap space-x-2">
                                    <a href="{{ route('backup.download', $backup['filename']) }}" class="text-blue-600 hover:underline">Download</a>
                                    <button type="button" @click="restoreOpen = true; mode = 'existing'; target = '{{ $backup['filename'] }}'" class="text-amber-600 hover:underline">Restore</button>
                                    <form method="POST" action="{{ route('backup.destroy', $backup['filename']) }}" class="inline" onsubmit="return confirm('Delete this backup file?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-center text-gray-400">No backups yet. Run one above.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t mt-6 pt-4">
                <button type="button" @click="restoreOpen = true; mode = 'upload'; target = ''" class="text-sm text-amber-600 hover:underline">Restore from an uploaded file instead &rarr;</button>
            </div>

            <div x-show="restoreOpen" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
                    <h3 class="font-semibold mb-1 text-red-700">Restore Database</h3>
                    <p class="text-sm text-gray-500 mb-4">This will overwrite ALL current data with the contents of the backup. This cannot be undone.</p>

                    <form method="POST" action="{{ route('backup.restore') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="hidden" name="source" :value="mode">

                        <template x-if="mode === 'existing'">
                            <p class="text-sm">Restoring from: <strong x-text="target"></strong></p>
                        </template>
                        <input type="hidden" name="existing_filename" :value="target" x-show="mode === 'existing'">

                        <template x-if="mode === 'upload'">
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Upload backup (.zip) or raw dump (.sql)</label>
                                <input type="file" name="file" accept=".zip,.sql" class="block w-full text-sm">
                            </div>
                        </template>

                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="confirm" value="1" required class="rounded border-gray-300">
                            I understand this will overwrite the current database.
                        </label>

                        <div class="flex justify-end gap-2">
                            <button type="button" @click="restoreOpen = false" class="px-4 py-2 text-gray-500 text-sm">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-md">Restore Now</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
