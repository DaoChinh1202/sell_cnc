<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
        $this->category = Category::query()->create([
            'name' => 'Inventory category',
            'slug' => 'inventory-category',
            'status' => 'active',
        ]);
    }

    public function test_combined_filters_match_name_or_sku_and_include_entire_boundary_days(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $first = $this->product(['name' => 'Lotus panel', 'created_at' => '2026-02-10 00:00:00']);
        $last = $this->product(['sku' => 'LOTUS-002', 'created_at' => '2026-02-12 23:59:59']);
        $this->product(['name' => 'Lotus before', 'created_at' => '2026-02-09 23:59:59']);
        $this->product(['sku' => 'LOTUS-AFTER', 'created_at' => '2026-02-13 00:00:00']);
        $this->product(['name' => 'Lotus inactive', 'status' => 'inactive', 'created_at' => '2026-02-11 12:00:00']);
        $this->product(['sku' => 'LOTUS-DRAFT', 'status' => 'draft', 'created_at' => '2026-02-11 12:00:00']);
        $this->product(['name' => 'Unrelated panel', 'created_at' => '2026-02-11 12:00:00']);
        $filters = ['q' => 'Lotus', 'status' => 'active', 'date_from' => '2026-02-10', 'date_to' => '2026-02-12'];

        $this->get(route('inventory', $filters))->assertOk()
            ->assertViewIs('inventory')
            ->assertViewHas('filters', $filters)
            ->assertViewHas('products', fn ($products) => $products->total() === 2
                && $products->pluck('id')->all() === [$last->id, $first->id]);
    }

    public function test_same_day_and_open_ended_date_filters_use_created_at_not_updated_at(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $before = $this->product(['created_at' => '2026-02-09 23:59:59', 'updated_at' => '2026-02-10 12:00:00']);
        $start = $this->product(['created_at' => '2026-02-10 00:00:00', 'updated_at' => '2026-02-20 12:00:00']);
        $end = $this->product(['created_at' => '2026-02-10 23:59:59']);
        $after = $this->product(['created_at' => '2026-02-11 00:00:00']);

        foreach ([
            [['date_from' => '2026-02-10', 'date_to' => '2026-02-10'], [$end->id, $start->id]],
            [['date_from' => '2026-02-10'], [$after->id, $end->id, $start->id]],
            [['date_to' => '2026-02-10'], [$end->id, $start->id, $before->id]],
        ] as [$filters, $ids]) {
            $this->get(route('inventory', $filters))->assertOk()
                ->assertViewHas('products', fn ($products) => $products->pluck('id')->all() === $ids);
        }
    }

    public function test_each_status_and_trimmed_search_can_be_filtered_independently(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach (['active', 'inactive', 'draft'] as $status) {
            $products[$status] = $this->product(['status' => $status]);
        }

        foreach ($products as $status => $product) {
            $this->get(route('inventory', ['status' => $status]))->assertOk()
                ->assertViewHas('products', fn ($page) => $page->pluck('id')->all() === [$product->id]);
        }
        $this->get(route('inventory', ['q' => '  '.$products['draft']->sku.'  ']))->assertOk()
            ->assertViewHas('search', $products['draft']->sku)
            ->assertViewHas('products', fn ($page) => $page->pluck('id')->all() === [$products['draft']->id]);
        $this->get(route('inventory', ['q' => 'no-such-product']))->assertOk()
            ->assertViewHas('products', fn ($page) => $page->total() === 0);
    }

    public function test_pagination_preserves_all_filters(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        for ($i = 0; $i < 11; $i++) {
            $this->product(['name' => 'Lotus panel', 'created_at' => '2026-02-10 12:00:00']);
        }
        $filters = ['q' => 'Lotus', 'status' => 'active', 'date_from' => '2026-02-10', 'date_to' => '2026-02-10'];
        $page = $this->get(route('inventory', $filters))->assertOk()->viewData('products');
        $this->assertSame(11, $page->total());
        $this->assertCount(10, $page->items());
        parse_str(parse_url($page->nextPageUrl(), PHP_URL_QUERY), $query);
        $this->assertEquals($filters + ['page' => '2'], $query);
        $this->get($page->nextPageUrl())->assertOk()
            ->assertViewHas('products', fn ($products) => $products->currentPage() === 2 && $products->count() === 1);
    }

    public static function invalidFilters(): array
    {
        return [
            'unknown status' => [['status' => 'archived'], 'status'],
            'array status' => [['status' => ['active']], 'status'],
            'array search' => [['q' => ['panel']], 'q'],
            'long search' => [['q' => str_repeat('a', 256)], 'q'],
            'invalid start' => [['date_from' => 'not-a-date'], 'date_from'],
            'impossible day' => [['date_to' => '2026-02-30'], 'date_to'],
            'timestamp instead of day' => [['date_from' => '2026-02-10 12:00:00'], 'date_from'],
            'invalid end' => [['date_to' => 'tomorrow'], 'date_to'],
            'reversed range' => [['date_from' => '2026-02-12', 'date_to' => '2026-02-10'], 'date_to'],
        ];
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_filters_are_rejected_for_inventory_and_bulk_without_mutation(array $filters, string $error): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = $this->product();
        Storage::disk('public')->put($product->image, 'image');
        $this->from(route('inventory'))->get(route('inventory', $filters))
            ->assertRedirect(route('inventory'))->assertSessionHasErrors($error);
        $this->from(route('inventory'))->post(route('products.bulk'), $filters + [
            'action' => 'delete', 'ids' => [$product->id],
        ])->assertRedirect(route('inventory'))->assertSessionHasErrors($error);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'active']);
        Storage::disk('public')->assertExists($product->image);
    }

    public static function statusActions(): array
    {
        return [['active'], ['inactive']];
    }

    #[DataProvider('statusActions')]
    public function test_bulk_status_changes_only_selected_products_and_preserves_filters_on_first_page(string $action): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $opposite = $action === 'active' ? 'inactive' : 'active';
        $selected = $this->product(['status' => $opposite]);
        $draft = $this->product(['status' => 'draft']);
        $untouched = $this->product(['status' => $opposite]);
        Storage::disk('public')->put($selected->image, 'image');
        $filters = ['q' => 'panel', 'status' => $opposite, 'date_from' => '2026-02-10', 'date_to' => '2026-02-12'];

        $this->from(route('inventory', $filters + ['page' => 3]))
            ->post(route('products.bulk'), $filters + [
                'action' => $action, 'ids' => [$selected->id, $draft->id], 'page' => 3,
            ])->assertRedirect(route('inventory', $filters))->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame($action, $selected->fresh()->status);
        $this->assertSame($action, $draft->fresh()->status);
        $this->assertSame($opposite, $untouched->fresh()->status);
        $this->assertDatabaseCount('products', 3);
        Storage::disk('public')->assertExists($selected->image);
    }

    public function test_bulk_delete_removes_selected_rows_and_only_unshared_safe_product_images(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $paths = ['products/unique.webp', 'products/shared.jpg', 'products/selected-shared.png',
            'categories/keep.png', 'products/nested/keep.jpg', 'products/keep.svg', 'products/../outside.png'];
        $selected = [];
        foreach ($paths as $path) {
            Storage::disk('public')->put($path, 'image');
            $selected[] = $this->product(['image' => $path])->id;
        }
        $selected[] = $this->product(['image' => 'products/selected-shared.png'])->id;
        $survivor = $this->product(['image' => 'products/shared.jpg']);
        $unselected = $this->product();
        Storage::disk('public')->put($unselected->image, 'untouched');
        $filters = ['q' => 'panel', 'status' => 'active', 'date_from' => '2026-02-10', 'date_to' => '2026-02-12'];

        $this->post(route('products.bulk'), $filters + ['action' => 'delete', 'ids' => $selected, 'page' => 4])
            ->assertRedirect(route('inventory', $filters))->assertSessionHasNoErrors()->assertSessionHas('success');

        foreach ($selected as $id) {
            $this->assertDatabaseMissing('products', ['id' => $id]);
        }
        $this->assertDatabaseCount('products', 2);
        $this->assertModelExists($survivor);
        $this->assertModelExists($unselected);
        Storage::disk('public')->assertMissing(['products/unique.webp', 'products/selected-shared.png']);
        Storage::disk('public')->assertExists([
            'products/shared.jpg', 'categories/keep.png', 'products/nested/keep.jpg',
            'products/keep.svg', 'outside.png', $unselected->image,
        ]);
    }

    public function test_cleanup_runs_after_the_bulk_transaction_and_failure_does_not_restore_deleted_rows(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $first = $this->product();
        $second = $this->product();
        $disk = Storage::disk('public');
        $disk->put($first->image, 'first');
        $disk->put($second->image, 'second');
        // RefreshDatabase owns an outer transaction; cleanup must leave only that transaction open.
        $transactionLevel = DB::transactionLevel();
        $cleanup = Mockery::mock($disk);
        $cleanup->shouldReceive('delete')->once()->with($first->image)->andReturnUsing(function () use ($first, $second, $transactionLevel) {
            $this->assertSame($transactionLevel, DB::transactionLevel());
            $this->assertModelMissing($first);
            $this->assertModelMissing($second);

            throw new \RuntimeException('Simulated storage failure');
        });
        $cleanup->shouldReceive('delete')->once()->with($second->image)
            ->andReturnUsing(fn ($path) => $disk->delete($path));
        Storage::shouldReceive('disk')->with('public')->andReturn($cleanup);

        $this->post(route('products.bulk'), ['action' => 'delete', 'ids' => [$first->id, $second->id]])
            ->assertRedirect(route('inventory'))->assertSessionHas('success')->assertSessionHas('warning');

        $this->assertDatabaseCount('products', 0);
        $disk->assertExists($first->image);
        $disk->assertMissing($second->image);
    }

    public function test_invalid_batches_are_rejected_atomically_for_every_action(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $first = $this->product(['status' => 'draft']);
        $second = $this->product(['status' => 'draft']);
        Storage::disk('public')->put($first->image, 'first');
        Storage::disk('public')->put($second->image, 'second');
        $snapshot = Product::orderBy('id')->get()->toArray();

        foreach (['active', 'inactive', 'delete'] as $action) {
            foreach ([
                [[], 'ids'],
                [['ids' => []], 'ids'],
                [['ids' => (string) $first->id], 'ids'],
                [['ids' => [$first->id, $first->id]], 'ids.0'],
                [['ids' => [$first->id, $second->id + 1000]], 'ids.1'],
                [['ids' => [$first->id, 'not-an-id']], 'ids.1'],
                [['ids' => [$first->id, null]], 'ids.1'],
                [['ids' => [$first->id, [$second->id]]], 'ids.1'],
            ] as [$payload, $error]) {
                $this->from(route('inventory'))->post(route('products.bulk'), ['action' => $action] + $payload)
                    ->assertRedirect(route('inventory'))->assertSessionHasErrors($error);
                $this->assertSame($snapshot, Product::orderBy('id')->get()->toArray());
                Storage::disk('public')->assertExists([$first->image, $second->image]);
            }
        }
        foreach ([[], ['action' => 'draft'], ['action' => ['delete']]] as $payload) {
            $this->post(route('products.bulk'), $payload + ['ids' => [$first->id, $second->id]])
                ->assertSessionHasErrors('action');
            $this->assertSame($snapshot, Product::orderBy('id')->get()->toArray());
            Storage::disk('public')->assertExists([$first->image, $second->image]);
        }
    }

    public function test_batch_limit_rejects_101_distinct_existing_ids_but_accepts_100(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $ids = [];
        for ($i = 0; $i < 101; $i++) {
            $ids[] = $this->product(['status' => 'draft'])->id;
        }
        foreach (['active', 'inactive', 'delete'] as $action) {
            $this->post(route('products.bulk'), ['action' => $action, 'ids' => $ids])->assertSessionHasErrors('ids');
            $this->assertSame(101, Product::where('status', 'draft')->count());
            $this->assertDatabaseCount('products', 101);
        }
        $this->post(route('products.bulk'), ['action' => 'active', 'ids' => array_slice($ids, 0, 100)])
            ->assertRedirect(route('inventory'))->assertSessionHasNoErrors();
        $this->assertSame(100, Product::where('status', 'active')->count());
        $this->assertDatabaseHas('products', ['id' => $ids[100], 'status' => 'draft']);
    }

    public function test_guests_cannot_view_inventory_or_perform_any_bulk_action(): void
    {
        $product = $this->product(['status' => 'draft']);
        Storage::disk('public')->put($product->image, 'image');
        $this->get(route('inventory'))->assertRedirect(route('signin'));
        foreach (['active', 'inactive', 'delete'] as $action) {
            $this->post(route('products.bulk'), ['action' => $action, 'ids' => [$product->id]])
                ->assertRedirect(route('signin'));
            $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'draft']);
            Storage::disk('public')->assertExists($product->image);
        }
    }

    public function test_non_admins_cannot_view_inventory_or_perform_any_bulk_action(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $product = $this->product(['status' => 'draft']);
        Storage::disk('public')->put($product->image, 'image');
        $this->get(route('inventory'))->assertForbidden();
        foreach (['active', 'inactive', 'delete'] as $action) {
            $this->post(route('products.bulk'), ['action' => $action, 'ids' => [$product->id]])->assertForbidden();
            $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'draft']);
            Storage::disk('public')->assertExists($product->image);
        }
    }

    private function product(array $attributes = []): Product
    {
        return Product::query()->forceCreate(array_replace([
            'category_id' => $this->category->id,
            'name' => 'CNC panel',
            'sku' => 'CNC-'.Str::uuid(),
            'price' => 100000,
            'status' => 'active',
            'image' => 'products/'.Str::uuid().'.webp',
        ], $attributes));
    }
}
