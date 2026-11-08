<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate($this->inventoryFilterRules($request));
        $search = trim($filters['q'] ?? '');
        $products = Product::query()
            ->with('category')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
            ))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->where('created_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->where('created_at', '<', Carbon::parse($date)->addDay()->startOfDay()))
            ->latest()
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('inventory', [
            'navActive' => 'inventory',
            'products' => $products,
            'search' => $search,
            'filters' => $filters,
        ]);
    }

    private function inventoryFilterRules(Request $request): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'draft'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
        ];
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            ...$this->inventoryFilterRules($request),
            'action' => ['required', Rule::in(['active', 'inactive', 'delete'])],
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:products,id'],
        ]);

        $images = DB::transaction(function () use ($data) {
            $products = Product::whereIn('id', $data['ids'])->lockForUpdate()->get();
            if ($products->count() !== count($data['ids'])) {
                throw ValidationException::withMessages(['ids' => 'Danh sách sản phẩm đã thay đổi. Vui lòng tải lại và chọn lại.']);
            }

            if ($data['action'] === 'delete') {
                Product::whereIn('id', $data['ids'])->delete();

                return $products->pluck('image')->filter()->unique()->all();
            }

            Product::whereIn('id', $data['ids'])->update(['status' => $data['action']]);

            return [];
        });

        // Delete files only after commit; a storage failure must not undo the database result.
        $cleanupFailed = false;
        foreach ($images as $image) {
            if (! preg_match('~\Aproducts/[a-zA-Z0-9_-]+\.(?:webp|png|jpe?g)\z~i', $image)
                || Product::where('image', $image)->exists()) {
                continue;
            }
            try {
                if (! Storage::disk('public')->delete($image)) {
                    throw new \RuntimeException('Không thể xóa ảnh sản phẩm: '.$image);
                }
            } catch (Throwable $exception) {
                report($exception);
                $cleanupFailed = true;
            }
        }

        $message = match ($data['action']) {
            'delete' => 'Đã xóa '.count($data['ids']).' sản phẩm.',
            'active' => 'Đã chuyển '.count($data['ids']).' sản phẩm sang hoạt động.',
            'inactive' => 'Đã chuyển '.count($data['ids']).' sản phẩm sang ngừng hoạt động.',
        };
        $response = to_route('inventory', array_intersect_key($data, array_flip(['q', 'status', 'date_from', 'date_to'])))
            ->with('success', $message);
        if ($cleanupFailed) {
            $response->with('warning', 'Sản phẩm đã được xóa nhưng một số ảnh chưa được dọn dẹp. Vui lòng kiểm tra nhật ký máy chủ.');
        }

        return $response;
    }

    public function create(): View
    {
        return view('products.create', [
            'navActive' => 'products.create',
            'categories' => Category::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function previewImage(Request $request, ProductImageService $productImages): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
    {
        $request->validate([
            'image' => $this->imageRules(true),
            'apply_watermark' => ['required', 'boolean'],
        ]);

        try {
            $bytes = $productImages->preview($request->file('image'), $request->boolean('apply_watermark'));
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Không thể xử lý ảnh xem trước. Vui lòng thử lại hoặc chọn ảnh khác.'], 500)
                ->header('Cache-Control', 'no-store, private');
        }

        return response($bytes)->withHeaders([
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function store(Request $request, ProductImageService $productImages): RedirectResponse
    {
        $data = $this->validatedData($request);
        $watermark = $request->boolean('apply_watermark', true);
        unset($data['apply_watermark']);
        $newImage = null;
        $savingProduct = false;

        try {
            $data['sku'] = $this->generateSku();
            $newImage = $watermark
                ? $productImages->storeWatermarked($request->file('image'))
                : $productImages->storeWithoutWatermark($request->file('image'));
            $data['image'] = $newImage;
            $savingProduct = true;
            Product::query()->create($data);
        } catch (Throwable $exception) {
            if ($newImage !== null) {
                Storage::disk('public')->delete($newImage);
            }

            report($exception);

            return back()->withInput()->withErrors($savingProduct ? [
                'product' => 'Không thể lưu sản phẩm. Vui lòng thử lại hoặc liên hệ quản trị viên.',
            ] : [
                'image' => 'Không thể xử lý ảnh. Vui lòng thử lại bằng ảnh JPEG, PNG hoặc WebP khác.',
            ]);
        }

        return to_route('inventory')->with('success', $watermark
            ? 'Đã thêm sản phẩm. Ảnh được lưu dưới dạng WebP có watermark.'
            : 'Đã thêm sản phẩm. Ảnh được lưu dưới dạng WebP không chèn watermark.');
    }

    public function show(Product $product): View
    {
        return view('products.show', [
            'navActive' => 'inventory',
            'product' => $product->load('category'),
        ]);
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'navActive' => 'inventory',
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product, ProductImageService $productImages): RedirectResponse
    {
        $data = $this->validatedData($request, $product);
        $watermark = $request->boolean('apply_watermark', true);
        unset($data['apply_watermark']);
        $newImage = null;
        $previousImage = $product->image;
        $savingProduct = false;

        try {
            if ($request->hasFile('image')) {
                $newImage = $watermark
                    ? $productImages->storeWatermarked($request->file('image'))
                    : $productImages->storeWithoutWatermark($request->file('image'));
                $data['image'] = $newImage;
            }

            $savingProduct = true;
            $product->update($data);
        } catch (Throwable $exception) {
            if ($newImage !== null) {
                Storage::disk('public')->delete($newImage);
            }

            report($exception);

            return back()->withInput()->withErrors($savingProduct ? [
                'product' => 'Không thể lưu sản phẩm. Vui lòng thử lại hoặc liên hệ quản trị viên.',
            ] : [
                'image' => 'Không thể xử lý ảnh. Vui lòng thử lại bằng ảnh JPEG, PNG hoặc WebP khác.',
            ]);
        }

        if ($newImage !== null) {
            Storage::disk('public')->delete($previousImage);
        }

        return to_route('products.show', $product)->with('success', 'Đã cập nhật sản phẩm.');
    }

    private function generateSku(): string
    {
        do {
            $sku = 'CNC-'.Str::upper(Str::random(10));
        } while (Product::query()->where('sku', $sku)->exists());

        return $sku;
    }

    private function imageRules(bool $required): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:5120',
            'dimensions:min_width=100,min_height=100,max_width=6000,max_height=6000',
        ];
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => $product === null
                ? ['exclude']
                : ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($product)],
            'description' => ['nullable', 'string'],
            'tags' => ['nullable', 'string', 'max:2000'],
            'free_download_url' => ['nullable', 'string', 'url:http,https', 'max:2048'],
            'file_format' => ['nullable', 'string', 'max:100'],
            'compatible_software' => ['nullable', 'string', 'max:255'],
            'file_size' => ['nullable', 'string', 'max:100'],
            'design_dimensions' => ['nullable', 'string', 'max:255'],
            'download_note' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive,draft'],
            'is_featured' => ['sometimes', 'boolean'],
            'apply_watermark' => ['sometimes', 'boolean'],
            'image' => $this->imageRules($product === null),
        ]);

        if (array_key_exists('tags', $data)) {
            $tags = collect(preg_split('/[,\r\n]+/u', $data['tags'] ?? ''))
                ->map(fn (string $tag): string => Str::lower(Str::squish($tag)))
                ->filter(fn (string $tag): bool => $tag !== '')
                ->unique()->values();

            if ($tags->count() > 20 || $tags->contains(fn (string $tag): bool => mb_strlen($tag) > 60)) {
                throw ValidationException::withMessages([
                    'tags' => 'Mỗi sản phẩm tối đa 20 từ khóa, mỗi từ khóa tối đa 60 ký tự.',
                ]);
            }

            $data['tags'] = $tags->all();
        }

        $data['is_featured'] = $request->boolean('is_featured');

        return $data;
    }
}
