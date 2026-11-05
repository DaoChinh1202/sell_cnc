<div class="mt-3" data-product-image-preview data-input-id="productImage" data-preview-url="{{ route('products.image-preview') }}">
  <p class="small text-muted mb-2">Khi chọn ảnh, ảnh sẽ được tải tạm lên máy chủ để xem trước, chưa lưu vào sản phẩm. Thay đổi tùy chọn watermark để xem ảnh đã xử lý tương ứng như khi lưu. Ảnh hiện tại của sản phẩm không bị thay đổi.</p>
  <p class="small text-muted mb-2" data-preview-status role="status" aria-live="polite"></p>
  <div class="d-flex gap-2 mb-2">
    <button type="button" class="btn btn-sm btn-outline-secondary" data-preview-retry hidden>Thử lại xem trước</button>
    <button type="button" class="btn btn-sm btn-outline-secondary" data-preview-clear hidden>Bỏ chọn ảnh mới</button>
  </div>
  <div class="border rounded p-3 bg-light-subtle" data-preview-panel hidden>
    <strong class="small d-block mb-2" data-preview-label></strong>
    <img data-preview-image alt="Xem trước ảnh sản phẩm mới đã xử lý trên máy chủ" class="img-fluid rounded d-block mx-auto" style="max-height: 360px; object-fit: contain;">
    <p class="small text-muted mt-2 mb-0">Ảnh đã được tối ưu và chuyển sang WebP theo tùy chọn watermark, chưa lưu vào sản phẩm.</p>
  </div>
</div>
