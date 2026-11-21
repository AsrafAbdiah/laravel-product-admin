<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Sanctum::actingAs(User::factory()->admin()->create());
    }

    /**
     * Multipart requests can't go through postJson(), so send a normal
     * POST that still asks for a JSON response.
     */
    private function multipart(string $uri, array $data)
    {
        return $this->withHeader('Accept', 'application/json')->post($uri, $data);
    }

    #[Test]
    public function a_product_can_be_created_with_an_image(): void
    {
        $response = $this->multipart('/api/products', [
            'category_id' => Category::factory()->create()->id,
            'name' => 'Desk Lamp',
            'price' => '59.90',
            'stock' => 8,
            'image' => UploadedFile::fake()->image('lamp.jpg', 600, 600),
        ])->assertCreated();

        $product = Product::firstOrFail();

        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
        $response->assertJsonPath('data.image_url', Storage::disk('public')->url($product->image_path));
    }

    #[Test]
    public function products_without_an_image_return_null_image_url(): void
    {
        $product = Product::factory()->create();

        $this->getJson("/api/products/{$product->id}")->assertOk()->assertJsonPath('data.image_url', null);
    }

    #[Test]
    public function uploading_a_new_image_replaces_and_deletes_the_old_file(): void
    {
        $product = Product::factory()->create([
            'image_path' => UploadedFile::fake()->image('old.jpg')->store('products', 'public'),
        ]);
        $oldPath = $product->image_path;

        // PHP can't read multipart bodies on PUT, so use POST with _method=PUT.
        $this->multipart("/api/products/{$product->id}", [
            '_method' => 'PUT',
            'image' => UploadedFile::fake()->image('new.png'),
        ])->assertOk();

        $product->refresh();
        $this->assertNotSame($oldPath, $product->image_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($product->image_path);
    }

    #[Test]
    public function the_image_can_be_removed_without_uploading_a_new_one(): void
    {
        $product = Product::factory()->create([
            'image_path' => UploadedFile::fake()->image('old.jpg')->store('products', 'public'),
        ]);
        $oldPath = $product->image_path;

        $this->putJson("/api/products/{$product->id}", ['remove_image' => true])
            ->assertOk()
            ->assertJsonPath('data.image_url', null);

        $this->assertNull($product->fresh()->image_path);
        Storage::disk('public')->assertMissing($oldPath);
    }

    #[Test]
    public function editing_other_fields_keeps_the_image(): void
    {
        $product = Product::factory()->create([
            'image_path' => UploadedFile::fake()->image('keep.jpg')->store('products', 'public'),
        ]);

        $this->putJson("/api/products/{$product->id}", ['name' => 'Renamed'])->assertOk();

        Storage::disk('public')->assertExists($product->fresh()->image_path);
    }

    #[Test]
    public function only_small_jpg_png_or_webp_images_are_accepted(): void
    {
        $base = [
            'category_id' => Category::factory()->create()->id,
            'name' => 'Lamp',
            'price' => 10,
            'stock' => 1,
        ];

        $this->multipart('/api/products', $base + ['image' => UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf')])
            ->assertStatus(422)->assertJsonValidationErrors('image');

        $this->multipart('/api/products', $base + ['image' => UploadedFile::fake()->image('huge.jpg')->size(3000)])
            ->assertStatus(422)->assertJsonValidationErrors('image');

        $this->assertDatabaseCount('products', 0);
    }

    #[Test]
    public function permanently_deleting_a_product_removes_its_image_file(): void
    {
        $product = Product::factory()->create([
            'image_path' => UploadedFile::fake()->image('gone.jpg')->store('products', 'public'),
        ]);
        $path = $product->image_path;
        $product->delete();

        // A soft delete keeps the file, so the product can be restored with its image.
        Storage::disk('public')->assertExists($path);

        $this->deleteJson("/api/products/{$product->id}/force")->assertOk();

        Storage::disk('public')->assertMissing($path);
    }

    #[Test]
    public function the_dashboard_form_accepts_an_image(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/products', [
                'category_id' => Category::factory()->create()->id,
                'name' => 'Wall Clock',
                'price' => '35.00',
                'stock' => 3,
                'enabled' => '1',
                'image' => UploadedFile::fake()->image('clock.webp'),
            ])
            ->assertRedirect();

        Storage::disk('public')->assertExists(Product::firstOrFail()->image_path);
    }
}
