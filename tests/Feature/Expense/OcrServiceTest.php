<?php
namespace Tests\Feature\Expense;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OcrServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ocr_endpoint_requires_auth(): void
    {
        $this->post(route('expenses.ocr'), [])->assertRedirect(route('login'));
    }

    public function test_ocr_endpoint_returns_json(): void
    {
        Storage::fake('public');
        Http::fake([
            'api.ocr.space/*' => Http::response([
                'ParsedResults' => [['ParsedText' => "Restaurant ABC\nTotal: ₱350.00\nDate: 2026-07-02"]],
                'OCRExitCode' => 1,
            ], 200),
        ]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('expenses.ocr'), [
            'receipt' => UploadedFile::fake()->image('receipt.jpg', 400, 600),
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['amount', 'date', 'description', 'confidence']);
    }

    public function test_ocr_endpoint_requires_receipt_file(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson(route('expenses.ocr'), []);
        $response->assertStatus(422);
    }

    public function test_ocr_log_is_recorded(): void
    {
        Storage::fake('public');
        Http::fake([
            'api.ocr.space/*' => Http::response([
                'ParsedResults' => [['ParsedText' => "Grocery Store\nTotal: ₱120.00"]],
                'OCRExitCode' => 1,
            ], 200),
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('expenses.ocr'), [
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
        ]);

        $this->assertDatabaseHas('ocr_logs', ['user_id' => $user->id]);
    }
}
