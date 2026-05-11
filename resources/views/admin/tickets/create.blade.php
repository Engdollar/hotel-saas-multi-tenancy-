<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-black" style="color: var(--text-primary);">New Support Ticket</h1>
            <p class="mt-1 text-sm text-muted">Create a ticket for your tenancy issue and track updates from the support team.</p>
        </div>
    </x-slot>

    <div class="panel p-5 sm:p-6 max-w-4xl">
        <form method="POST" action="{{ route('admin.tickets.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="subject" class="text-sm font-semibold" style="color: var(--text-primary);">Subject</label>
                <input id="subject" name="subject" value="{{ old('subject') }}" required class="form-input mt-2">
                <x-input-error :messages="$errors->get('subject')" class="mt-2" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="category" class="text-sm font-semibold" style="color: var(--text-primary);">Category</label>
                    <input id="category" name="category" value="{{ old('category', 'general') }}" required class="form-input mt-2" placeholder="billing, access, performance">
                    <x-input-error :messages="$errors->get('category')" class="mt-2" />
                </div>
                <div>
                    <label for="priority" class="text-sm font-semibold" style="color: var(--text-primary);">Priority</label>
                    <select id="priority" name="priority" class="form-input mt-2" required>
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority }}" @selected(old('priority', 'medium') === $priority)>{{ str($priority)->headline() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                </div>
            </div>

            <div>
                <label for="description" class="text-sm font-semibold" style="color: var(--text-primary);">Description</label>
                <textarea id="description" name="description" rows="7" required class="form-input mt-2">{{ old('description') }}</textarea>
                <p class="mt-2 text-xs text-muted">Paste text, screenshots, or WhatsApp images directly into the editor.</p>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.tickets.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Submit ticket</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const descriptionInput = document.querySelector('#description');

            if (!descriptionInput || !window.initializeSupportEditor) {
                return;
            }

            window.initializeSupportEditor(descriptionInput, {
                uploadUrl: @json(route('admin.tickets.editor.upload-image')),
            });
        });
    </script>
</x-app-layout>
