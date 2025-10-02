<x-guest-layout>
    <div class="max-w-md mx-auto bg-white dark:bg-gray-800 p-6 rounded shadow">
        <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-gray-100">Сброс пароля</h2>

        @if ($errors->any())
            <div class="mb-4 text-red-500">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-300">Email</label>
                <input type="email" name="email" value="{{ $email }}" readonly
                       class="mt-1 block w-full border-gray-300 rounded dark:bg-gray-700 dark:text-gray-200">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-300">Новый пароль</label>
                <input type="password" name="password" required
                       class="mt-1 block w-full border-gray-300 rounded dark:bg-gray-700 dark:text-gray-200">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-300">Подтверждение пароля</label>
                <input type="password" name="password_confirmation" required
                       class="mt-1 block w-full border-gray-300 rounded dark:bg-gray-700 dark:text-gray-200">
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                Сбросить пароль
            </button>
        </form>
    </div>
</x-guest-layout>
