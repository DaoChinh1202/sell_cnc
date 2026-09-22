<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class CategoryImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_create_stores_each_allowed_format_unchanged_without_watermark(): void
    {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
            $image = UploadedFile::fake()->image('category.'.$extension, 320, 240);
            $bytes = file_get_contents($image->getRealPath());
            $data = $this->categoryData(['slug' => 'category-'.$extension, 'image' => $image]);

            $this->post(route('categories.store'), $data)
                ->assertRedirect(route('categories.index'))->assertSessionHasNoErrors();

            $category = Category::where('slug', $data['slug'])->firstOrFail();
            $this->assertMatchesRegularExpression('/^categories\/[a-zA-Z0-9]{40}\.(jpg|jpeg|png|webp)$/', $category->image);
            Storage::disk('public')->assertExists($category->image);
            $this->assertSame($bytes, Storage::disk('public')->get($category->image));
        }

        $this->assertDatabaseCount('categories', 4);
        $this->assertCount(4, Storage::disk('public')->allFiles('categories'));
    }

    public function test_create_requires_an_image_and_displays_validation_errors(): void
    {
        $this->from(route('categories.index'))->post(route('categories.store'), $this->categoryData())
            ->assertRedirect(route('categories.index'))->assertSessionHasErrors('image');

        $this->assertDatabaseCount('categories', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $message = session('errors')->first('image');

        $this->get(route('categories.index'))->assertOk()
            ->assertSee('Không thể lưu danh mục.')->assertSee($message);
    }

    public function test_create_rejects_unsupported_and_disguised_files(): void
    {
        foreach ([
            UploadedFile::fake()->create('category.txt', 1, 'text/plain'),
            UploadedFile::fake()->createWithContent('category.jpg', 'This is not an image.'),
            UploadedFile::fake()->createWithContent('category.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"></svg>'),
            UploadedFile::fake()->image('category.gif', 200, 200),
        ] as $image) {
            $this->from(route('categories.index'))->post(route('categories.store'), $this->categoryData(['image' => $image]))
                ->assertRedirect(route('categories.index'))->assertSessionHasErrors('image');
        }

        $this->assertDatabaseCount('categories', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_create_rejects_images_larger_than_five_megabytes(): void
    {
        $image = UploadedFile::fake()->image('category.jpg', 200, 200)->size(5121);

        $this->from(route('categories.index'))->post(route('categories.store'), $this->categoryData(['image' => $image]))
            ->assertRedirect(route('categories.index'))->assertSessionHasErrors('image');

        $this->assertDatabaseCount('categories', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_create_rejects_either_dimension_outside_the_limits(): void
    {
        foreach ([[99, 100], [100, 99], [6001, 100], [100, 6001]] as [$width, $height]) {
            $image = UploadedFile::fake()->image('category.png', $width, $height);

            $this->from(route('categories.index'))->post(route('categories.store'), $this->categoryData(['image' => $image]))
                ->assertRedirect(route('categories.index'))->assertSessionHasErrors('image');
        }

        $this->assertDatabaseCount('categories', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_create_accepts_inclusive_size_and_dimension_limits(): void
    {
        foreach ([[100, 100], [6000, 100], [100, 6000]] as [$width, $height]) {
            $image = UploadedFile::fake()->image('category.jpg', $width, $height)->size(5120);

            $this->post(route('categories.store'), $this->categoryData([
                'slug' => 'category-'.$width.'-'.$height,
                'image' => $image,
            ]))->assertRedirect(route('categories.index'))->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('categories', 3);
        $this->assertCount(3, Storage::disk('public')->allFiles('categories'));
    }

    public function test_update_without_a_file_preserves_the_existing_image(): void
    {
        $category = $this->categoryWithImage();
        $previousImage = $category->image;
        $bytes = Storage::disk('public')->get($previousImage);

        foreach ([[], ['image' => null], ['image' => '']] as $imageInput) {
            $this->put(route('categories.update', $category), $this->categoryData([
                'name' => 'Danh mục đã sửa',
                ...$imageInput,
            ]))->assertRedirect(route('categories.index'))->assertSessionHasNoErrors();

            $this->assertSame('Danh mục đã sửa', $category->fresh()->name);
            $this->assertSame($previousImage, $category->fresh()->image);
            $this->assertSame($bytes, Storage::disk('public')->get($previousImage));
            $this->assertSame([$previousImage], Storage::disk('public')->allFiles());
        }
    }

    public function test_replacement_keeps_original_bytes_and_deletes_the_old_image_only_after_update(): void
    {
        $category = $this->categoryWithImage();
        $previousImage = $category->image;
        $image = UploadedFile::fake()->image('replacement.png', 240, 320);
        $bytes = file_get_contents($image->getRealPath());

        Event::listen('eloquent.updated: '.Category::class, function (Category $updated) use ($previousImage): void {
            Storage::disk('public')->assertExists($previousImage);
            Storage::disk('public')->assertExists($updated->image);
            $this->assertDatabaseHas('categories', ['id' => $updated->id, 'image' => $updated->image]);
        });

        $this->put(route('categories.update', $category), $this->categoryData(['image' => $image]))
            ->assertRedirect(route('categories.index'))->assertSessionHasNoErrors();

        $newImage = $category->fresh()->image;
        $this->assertNotSame($previousImage, $newImage);
        $this->assertStringStartsWith('categories/', $newImage);
        $this->assertSame($bytes, Storage::disk('public')->get($newImage));
        Storage::disk('public')->assertMissing($previousImage);
        $this->assertSame([$newImage], Storage::disk('public')->allFiles());
    }

    public function test_invalid_replacement_preserves_the_category_and_its_image(): void
    {
        $category = $this->categoryWithImage();
        $bytes = Storage::disk('public')->get($category->image);

        $this->from(route('categories.index'))->put(route('categories.update', $category), $this->categoryData([
            'name' => 'Must not be saved',
            'image' => UploadedFile::fake()->image('invalid.gif', 200, 200),
        ]))->assertRedirect(route('categories.index'))->assertSessionHasErrors('image');

        $this->assertSame($category->name, $category->fresh()->name);
        $this->assertSame($category->image, $category->fresh()->image);
        $this->assertSame($bytes, Storage::disk('public')->get($category->image));
        $this->assertSame([$category->image], Storage::disk('public')->allFiles());
    }

    public function test_deleting_an_empty_category_also_deletes_its_image(): void
    {
        $category = $this->categoryWithImage();

        Event::listen('eloquent.deleted: '.Category::class, function () use ($category): void {
            Storage::disk('public')->assertExists($category->image);
        });

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        Storage::disk('public')->assertMissing($category->image);
    }

    public function test_a_category_with_products_cannot_be_deleted_and_keeps_its_image(): void
    {
        $category = $this->categoryWithImage();
        $bytes = Storage::disk('public')->get($category->image);
        $product = $category->products()->create(['name' => 'Test product', 'sku' => 'CATEGORY-001', 'status' => 'active']);

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'image' => $category->image]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertSame($bytes, Storage::disk('public')->get($category->image));
    }

    public function test_legacy_category_without_an_image_can_be_listed_updated_and_deleted(): void
    {
        $category = Category::create($this->categoryData());
        $this->assertNull($category->fresh()->image);

        $this->get(route('categories.index'))->assertOk()->assertSee($category->name)->assertSee('Chưa có ảnh');

        $this->put(route('categories.update', $category), $this->categoryData(['name' => 'Legacy category updated']))
            ->assertRedirect(route('categories.index'))->assertSessionHasNoErrors();

        $this->assertSame('Legacy category updated', $category->fresh()->name);
        $this->assertNull($category->fresh()->image);

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_legacy_category_can_receive_its_first_image_on_update(): void
    {
        $category = Category::create($this->categoryData());
        $image = UploadedFile::fake()->image('first.png', 200, 200);
        $bytes = file_get_contents($image->getRealPath());

        $this->put(route('categories.update', $category), $this->categoryData(['image' => $image]))
            ->assertRedirect(route('categories.index'))->assertSessionHasNoErrors();

        $this->assertStringStartsWith('categories/', $category->fresh()->image);
        $this->assertSame($bytes, Storage::disk('public')->get($category->fresh()->image));
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_admin_forms_use_multipart_uploads_and_show_the_thumbnail_and_preview(): void
    {
        $category = $this->categoryWithImage();
        $imageUrl = Storage::disk('public')->url($category->image);

        $response = $this->get(route('categories.index'))->assertOk()
            ->assertSee('action="'.route('categories.store').'" method="POST" enctype="multipart/form-data"', false)
            ->assertSee('action="'.route('categories.update', $category).'" method="POST" enctype="multipart/form-data"', false)
            ->assertSee('accept="image/jpeg,image/png,image/webp"', false)
            ->assertSee('không watermark')
            ->assertSee('Để trống để giữ ảnh hiện tại.');

        $this->assertSame(2, substr_count($response->getContent(), 'src="'.$imageUrl.'"'));
        $this->assertMatchesRegularExpression('/<input[^>]*id="imageNew"[^>]*\srequired[^>]*>/', $response->getContent());
        $this->assertMatchesRegularExpression('/<input[^>]*id="image'.$category->id.'"[^>]*>/', $response->getContent());
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*id="image'.$category->id.'"[^>]*\srequired[^>]*>/', $response->getContent());
    }

    #[DataProvider('writeFailures')]
    public function test_failed_create_write_cleans_partial_files_and_does_not_create_a_category(bool $throw): void
    {
        $this->failImageWrites($throw);

        $this->from(route('categories.index'))->post(route('categories.store'), $this->categoryData([
            'image' => UploadedFile::fake()->image('category.jpg', 200, 200),
        ]))->assertRedirect(route('categories.index'))->assertSessionHasErrors('image')
            ->assertSessionHasInput('name', 'Danh mục CNC');

        $this->assertDatabaseCount('categories', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    #[DataProvider('writeFailures')]
    public function test_failed_replacement_write_cleans_partial_files_and_preserves_the_old_image(bool $throw): void
    {
        $category = $this->categoryWithImage();
        $bytes = Storage::disk('public')->get($category->image);
        $this->failImageWrites($throw);

        $this->from(route('categories.index'))->put(route('categories.update', $category), $this->categoryData([
            'name' => 'Must not be saved',
            'image' => UploadedFile::fake()->image('replacement.jpg', 200, 200),
        ]))->assertRedirect(route('categories.index'))->assertSessionHasErrors('image');

        $this->assertSame($category->name, $category->fresh()->name);
        $this->assertSame($category->image, $category->fresh()->image);
        $this->assertSame($bytes, Storage::disk('public')->get($category->image));
        $this->assertSame([$category->image], Storage::disk('public')->allFiles());
    }

    public function test_database_insert_failure_removes_the_new_upload(): void
    {
        $existing = Category::create($this->categoryData(['slug' => 'taken']));
        Event::listen('eloquent.creating: '.Category::class, function (Category $category): void {
            $category->slug = 'taken';
        });

        $this->from(route('categories.index'))->post(route('categories.store'), $this->categoryData([
            'image' => UploadedFile::fake()->image('category.jpg', 200, 200),
        ]))->assertRedirect(route('categories.index'))->assertSessionHasErrors('image');

        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseHas('categories', ['id' => $existing->id, 'slug' => 'taken']);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_database_update_failure_removes_the_new_upload_and_preserves_the_old_image(): void
    {
        $category = $this->categoryWithImage();
        $bytes = Storage::disk('public')->get($category->image);
        Category::create($this->categoryData(['slug' => 'taken']));
        Event::listen('eloquent.updating: '.Category::class, function (Category $category): void {
            $category->slug = 'taken';
        });

        $this->from(route('categories.index'))->put(route('categories.update', $category), $this->categoryData([
            'name' => 'Must not be saved',
            'image' => UploadedFile::fake()->image('replacement.jpg', 200, 200),
        ]))->assertRedirect(route('categories.index'))->assertSessionHasErrors('image');

        $this->assertDatabaseCount('categories', 2);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'image' => $category->image,
        ]);
        $this->assertSame($bytes, Storage::disk('public')->get($category->image));
        $this->assertSame([$category->image], Storage::disk('public')->allFiles());
    }

    public function test_cancelled_delete_preserves_the_category_and_its_image(): void
    {
        $category = $this->categoryWithImage();
        $bytes = Storage::disk('public')->get($category->image);
        Event::listen('eloquent.deleting: '.Category::class, fn () => false);

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'image' => $category->image]);
        $this->assertSame($bytes, Storage::disk('public')->get($category->image));
    }

    public static function writeFailures(): array
    {
        return [
            'returns false' => [false],
            'throws an exception' => [true],
        ];
    }

    private function categoryData(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Danh mục CNC',
            'slug' => 'cnc',
            'description' => 'Máy và phụ kiện CNC',
            'status' => 'active',
        ], $overrides);
    }

    private function categoryWithImage(): Category
    {
        $image = UploadedFile::fake()->image('original.jpg', 200, 200);
        $path = 'categories/original.jpg';
        Storage::disk('public')->put($path, file_get_contents($image->getRealPath()));

        return Category::create($this->categoryData(['image' => $path]));
    }

    private function failImageWrites(bool $throw): void
    {
        $disk = Storage::disk('public');
        $failingDisk = Mockery::mock($disk);
        $failingDisk->shouldReceive('putFileAs')->once()->andReturnUsing(
            function (string $directory, UploadedFile $file, string $name) use ($disk, $throw): bool {
                $disk->put($directory.'/'.$name, 'Incomplete upload');

                if ($throw) {
                    throw new RuntimeException('Simulated image write failure.');
                }

                return false;
            }
        );
        Storage::set('public', $failingDisk);
    }
}
