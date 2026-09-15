<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Users</h2>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-3">Add User</h3>
            <form method="POST" action="{{ route('users.store') }}" class="space-y-3">
                @csrf
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" type="email" name="email" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="password" value="Password" />
                    <x-text-input id="password" type="password" name="password" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="role" value="Role" />
                    <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="cashier">Cashier</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <x-primary-button>Add User</x-primary-button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-5">
            <table class="w-full text-sm" x-data="{ editing: null }">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Name</th>
                        <th class="py-2">Email</th>
                        <th class="py-2">Role</th>
                        <th class="py-2">Active</th>
                        <th class="py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-b last:border-0" x-show="editing !== {{ $user->id }}">
                            <td class="py-2">{{ $user->name }}</td>
                            <td class="py-2">{{ $user->email }}</td>
                            <td class="py-2 capitalize">{{ $user->role }}</td>
                            <td class="py-2">{{ $user->is_active ? 'Yes' : 'No' }}</td>
                            <td class="py-2 text-right space-x-2 whitespace-nowrap">
                                <button type="button" @click="editing = {{ $user->id }}" class="text-blue-600 hover:underline">Edit</button>
                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline" onsubmit="return confirm('Delete this user?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        <tr x-show="editing === {{ $user->id }}" x-cloak>
                            <td colspan="5" class="py-2">
                                <form method="POST" action="{{ route('users.update', $user) }}" class="grid grid-cols-2 gap-2 items-center">
                                    @csrf @method('PUT')
                                    <input type="text" name="name" value="{{ $user->name }}" placeholder="Name" class="rounded-md border-gray-300 shadow-sm text-sm" required>
                                    <input type="email" name="email" value="{{ $user->email }}" placeholder="Email" class="rounded-md border-gray-300 shadow-sm text-sm" required>
                                    <input type="password" name="password" placeholder="New password (optional)" class="rounded-md border-gray-300 shadow-sm text-sm">
                                    <select name="role" class="rounded-md border-gray-300 shadow-sm text-sm">
                                        <option value="cashier" @selected($user->role === 'cashier')>Cashier</option>
                                        <option value="manager" @selected($user->role === 'manager')>Manager</option>
                                        <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                    </select>
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="is_active" value="1" @checked($user->is_active) class="rounded border-gray-300"> Active
                                    </label>
                                    <div class="space-x-2">
                                        <button type="submit" class="text-green-600 text-sm hover:underline">Save</button>
                                        <button type="button" @click="editing = null" class="text-gray-500 text-sm hover:underline">Cancel</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-center text-gray-400">No users yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>
