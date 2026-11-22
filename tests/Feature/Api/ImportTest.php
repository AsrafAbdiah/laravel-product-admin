<?php

namespace Tests\Feature\Api;

use App\Exports\ProductsExport;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        Sanctum::actingAs($this->admin, ['*']);

        Category::factory()->create(['name' => 'Electronics']);
        Category::factory()->create(['name' => 'Books']);
    }

    /**
     * Build a real .xlsx file in memory from the given rows.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function xlsx(array $rows): UploadedFile
    {
        // WithStrictNullComparison so a 0 is written as 0, not an empty cell.
        $export = new class($rows) implements FromArray, WithHeadings, WithStrictNullComparison
        {
            public function __construct(private array $rows) {}

            public function array(): array
            {
                return $this->rows;
            }

            public function headings(): array
            {
                return ['Name', 'Category', 'Description', 'Price', 'Stock', 'Status'];
            }
        };

        return UploadedFile::fake()->createWithContent('products.xlsx', Excel::raw($export, ExcelFormat::XLSX));
    }

    private function upload(UploadedFile $file)
    {
        return $this->withHeader('Accept', 'application/json')->post('/api/products/import', ['file' => $file]);
    }

    #[Test]
    public function valid_rows_are_imported(): void
    {
        $this->upload($this->xlsx([
            ['USB-C Hub', 'Electronics', '7-in-1 adapter', 89.9, 15, 'enabled'],
            ['Clean Code', 'books', null, 120, 4, 'Disabled'],
            ['Desk Speaker', 'ELECTRONICS', null, 59, 0, null],
        ]))
            ->assertOk()
            ->assertJsonPath('imported', 3)
            ->assertJsonPath('failed', []);

        $this->assertDatabaseHas('products', ['name' => 'USB-C Hub', 'price' => 89.9, 'stock' => 15, 'enabled' => true]);
        // Category names match case-insensitively; "Disabled" works in any case.
        $this->assertDatabaseHas('products', ['name' => 'Clean Code', 'enabled' => false]);
        // A blank status means enabled.
        $this->assertDatabaseHas('products', ['name' => 'Desk Speaker', 'enabled' => true]);
    }

    #[Test]
    public function invalid_rows_are_skipped_and_reported_with_their_row_number(): void
    {
        $this->upload($this->xlsx([
            ['Good Product', 'Electronics', null, 10, 5, 'enabled'],   // row 2
            ['No Price', 'Electronics', null, null, 5, 'enabled'],     // row 3
            ['Unknown Category', 'Garden', null, 10, 5, 'enabled'],    // row 4
            ['Bad Status', 'Books', null, 10, 5, 'maybe'],              // row 5
        ]))
            ->assertOk()
            ->assertJsonPath('imported', 1)
            ->assertJsonCount(3, 'failed')
            ->assertJsonPath('failed.0.row', 3)
            ->assertJsonPath('failed.1.row', 4)
            ->assertJsonPath('failed.1.errors.0', 'The category does not exist. Create it first, or fix the spelling.')
            ->assertJsonPath('failed.2.row', 5);

        $this->assertSame(['Good Product'], Product::pluck('name')->all());
    }

    #[Test]
    public function imported_products_are_labelled_in_the_stock_history(): void
    {
        $this->upload($this->xlsx([['Monitor Arm', 'Electronics', null, 150, 12, 'enabled']]))->assertOk();

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => Product::firstOrFail()->id,
            'user_id' => $this->admin->id,
            'new_stock' => 12,
            'reason' => StockMovement::REASON_IMPORTED,
        ]);
    }

    #[Test]
    public function the_imported_label_does_not_leak_into_later_changes(): void
    {
        $this->upload($this->xlsx([['Monitor Arm', 'Electronics', null, 150, 12, 'enabled']]))->assertOk();
        $product = Product::firstOrFail();

        $this->putJson("/api/products/{$product->id}", ['stock' => 20])->assertOk();

        $this->assertSame(StockMovement::REASON_UPDATED, $product->stockMovements()->latest('id')->value('reason'));
    }

    #[Test]
    public function an_exported_file_can_be_imported_again_including_zero_stock(): void
    {
        $category = Category::where('name', 'Electronics')->firstOrFail();
        Product::factory()->for($category)->create(['name' => 'Sold Out Cable', 'stock' => 0, 'price' => 9.5]);
        Product::factory()->for($category)->disabled()->create(['name' => 'Hidden Charger', 'stock' => 7]);

        $exported = Excel::raw(new ProductsExport(app(ProductService::class)->query()), ExcelFormat::XLSX);

        $this->upload(UploadedFile::fake()->createWithContent('export.xlsx', $exported))
            ->assertOk()
            ->assertJsonPath('imported', 2)
            ->assertJsonPath('failed', []);

        // Each product now exists twice: the original and the imported copy.
        $this->assertSame(2, Product::where('name', 'Sold Out Cable')->where('stock', 0)->count());
        $this->assertSame(2, Product::where('name', 'Hidden Charger')->where('enabled', false)->count());
    }

    #[Test]
    public function csv_files_are_accepted(): void
    {
        $csv = "Name,Category,Description,Price,Stock,Status\nPaperback Notebook,Books,,12.50,30,enabled\n";

        $this->upload(UploadedFile::fake()->createWithContent('products.csv', $csv))
            ->assertOk()
            ->assertJsonPath('imported', 1);

        $this->assertDatabaseHas('products', ['name' => 'Paperback Notebook', 'stock' => 30]);
    }

    #[Test]
    public function only_excel_or_csv_files_are_accepted(): void
    {
        $this->upload(UploadedFile::fake()->create('products.pdf', 50, 'application/pdf'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        $this->withHeader('Accept', 'application/json')->post('/api/products/import', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    #[Test]
    public function dashboard_import_shows_a_summary(): void
    {
        $this->actingAs($this->admin)
            ->post('/products/import', ['file' => $this->xlsx([
                ['Lamp', 'Electronics', null, 25, 3, 'enabled'],
                ['Broken', 'Nowhere', null, 25, 3, 'enabled'],
            ])])
            ->assertRedirect(route('products.import'));

        $this->actingAs($this->admin)->get(route('products.import'))
            ->assertOk()
            ->assertSee('1 product(s) imported, 1 row(s) skipped')
            ->assertSee('The category does not exist');
    }

    #[Test]
    public function the_import_template_can_be_downloaded(): void
    {
        $this->actingAs($this->admin)->get(route('products.import.template'))
            ->assertOk()
            ->assertDownload('product-import-template.xlsx');
    }
}
