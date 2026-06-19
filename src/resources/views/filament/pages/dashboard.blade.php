<x-filament-panels::page>

    <div class="space-y-8">

        <div>
            <h1 class="text-3xl font-bold">
                Welcome to Vocafy Admin
            </h1>

            <p class="text-gray-500">
                Manage your English learning platform.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">

            <div class="rounded-xl bg-white p-6 shadow">
                <div class="text-sm text-gray-500">
                    Users
                </div>

                <div class="mt-2 text-4xl font-bold">
                    {{ $this->users['count'] }}
                </div>
            </div>

            <div class="rounded-xl bg-white p-6 shadow">
                <div class="text-sm text-gray-500">
                    Categories
                </div>

                <div class="mt-2 text-4xl font-bold">
                    {{ $this->categories }}
                </div>
            </div>

            <div class="rounded-xl bg-white p-6 shadow">
                <div class="text-sm text-gray-500">
                    Topics
                </div>

                <div class="mt-2 text-4xl font-bold">
                    {{ $this->topics }}
                </div>
            </div>

            <div class="rounded-xl bg-white p-6 shadow">
                <div class="text-sm text-gray-500">
                    Vocabularies
                </div>

                <div class="mt-2 text-4xl font-bold">
                    {{ $this->vocabularies }}
                </div>
            </div>

            <div class="rounded-xl bg-white p-6 shadow">
                <div class="text-sm text-gray-500">
                    Lessons
                </div>

                <div class="mt-2 text-4xl font-bold">
                    {{ $this->lessons }}
                </div>
            </div>

            <div class="rounded-xl bg-white p-6 shadow">
                <div class="text-sm text-gray-500">
                    Quizzes
                </div>

                <div class="mt-2 text-4xl font-bold">
                    {{ $this->quizzes }}
                </div>
            </div>

        </div>

    </div>

</x-filament-panels::page>