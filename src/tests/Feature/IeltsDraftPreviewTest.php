<?php

namespace Tests\Feature;

use App\Models\IeltsTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IeltsDraftPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_authorized_staff_can_preview_a_draft_and_cannot_start_it(): void
    {
        $this->seed(\Database\Seeders\IeltsSeeder::class);
        $test = IeltsTest::where('slug', 'cambridge-ielts-18-academic-test-1')->firstOrFail();
        $test->update(['is_published' => false]);
        $previewUrl = route('ielts.tests.show', ['slug' => $test->slug, 'preview' => 1]);

        $this->get($previewUrl)->assertNotFound();
        $this->actingAs(User::factory()->create())->get($previewUrl)->assertNotFound();

        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get($previewUrl)
            ->assertOk()
            ->assertSee('Đây là bản xem trước.')
            ->assertSee('Bộ đề chưa sẵn sàng để thi');
        $this->post(route('ielts.tests.start', $test->slug))->assertNotFound();
    }
}
