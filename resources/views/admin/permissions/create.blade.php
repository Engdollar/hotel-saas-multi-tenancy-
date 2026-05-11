<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white">Create Permission</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Add a custom permission to the RBAC catalog.</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('admin.permissions.store') }}" class="panel p-6 sm:p-8">
        @csrf
        @include('admin.permissions._form')
    </form>
</x-app-layout>