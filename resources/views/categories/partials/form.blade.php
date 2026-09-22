<div class="mb-3">
  <label for="name{{ $category?->id ?? 'New' }}" class="form-label">Tên danh mục</label>
  <input type="text" class="form-control" id="name{{ $category?->id ?? 'New' }}" name="name" value="{{ old('name', $category?->name) }}" required>
</div>
<div class="mb-3">
  <label for="slug{{ $category?->id ?? 'New' }}" class="form-label">Slug</label>
  <input type="text" class="form-control" id="slug{{ $category?->id ?? 'New' }}" name="slug" value="{{ old('slug', $category?->slug) }}" placeholder="Tự tạo từ tên nếu để trống">
</div>
<div class="mb-3">
  <label for="description{{ $category?->id ?? 'New' }}" class="form-label">Mô tả</label>
  <textarea class="form-control" id="description{{ $category?->id ?? 'New' }}" name="description" rows="3">{{ old('description', $category?->description) }}</textarea>
</div>
<div class="mb-3">
  <label for="image{{ $category?->id ?? 'New' }}" class="form-label">Ảnh danh mục{{ $category === null ? ' (bắt buộc)' : '' }}</label>
  @if ($category?->image)
    <div class="mb-2"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($category->image) }}" alt="Ảnh hiện tại của {{ $category->name }}" class="rounded object-fit-cover" width="96" height="96" loading="lazy"></div>
  @endif
  <input type="file" class="form-control @error('image') is-invalid @enderror" id="image{{ $category?->id ?? 'New' }}" name="image" accept="image/jpeg,image/png,image/webp" @required($category === null)>
  <div class="form-text">Chỉ nhận JPEG, PNG, WebP; tối đa 5 MB; kích thước từ 100×100 đến 6000×6000 px. Ảnh được lưu nguyên bản, không watermark.{{ $category !== null ? ' Để trống để giữ ảnh hiện tại.' : '' }}</div>
  @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div>
  <label for="status{{ $category?->id ?? 'New' }}" class="form-label">Trạng thái</label>
  <select class="form-select" id="status{{ $category?->id ?? 'New' }}" name="status" required>
    <option value="active" @selected(old('status', $category?->status ?? 'active') === 'active')>Hoạt động</option>
    <option value="inactive" @selected(old('status', $category?->status) === 'inactive')>Ngừng hoạt động</option>
  </select>
</div>
