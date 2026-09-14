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
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3 mt-2">
            @livewire(\App\Filament\Widgets\StatsOverview::class)
            @livewire(\App\Filament\Widgets\UserChart::class)
        </div>

    </div>

</x-filament-panels::page>

