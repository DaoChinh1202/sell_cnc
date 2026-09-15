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
<div>
  <label for="status{{ $category?->id ?? 'New' }}" class="form-label">Trạng thái</label>
  <select class="form-select" id="status{{ $category?->id ?? 'New' }}" name="status" required>
    <option value="active" @selected(old('status', $category?->status ?? 'active') === 'active')>Hoạt động</option>
    <option value="inactive" @selected(old('status', $category?->status) === 'inactive')>Ngừng hoạt động</option>
  </select>
</div>
