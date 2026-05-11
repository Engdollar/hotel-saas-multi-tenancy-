<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white">Edit Role</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Refine role naming and permission coverage.</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="panel p-6 sm:p-8">
        @csrf
        @method('PUT')
        @include('admin.roles._form')
    </form>
</x-app-layout>