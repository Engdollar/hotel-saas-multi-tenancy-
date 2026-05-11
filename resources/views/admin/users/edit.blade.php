<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white">Edit User</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Update account details, credentials, and role assignments.</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data" class="panel p-6 sm:p-8">
        @csrf
        @method('PUT')
        @include('admin.users._form')
    </form>
</x-app-layout>