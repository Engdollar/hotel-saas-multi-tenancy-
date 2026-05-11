<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white">Create User</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Add a new account and assign its initial roles.</p>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data" class="panel p-5 sm:p-7">
        @csrf
        @include('admin.users._form')
    </form>
</x-app-layout>