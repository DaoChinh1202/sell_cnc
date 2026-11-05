<div class="mt-3">
  <input type="hidden" name="apply_watermark" value="0">
  <div class="form-check form-switch">
    <input class="form-check-input @error('apply_watermark') is-invalid @enderror" type="checkbox" role="switch" id="productWatermark" name="apply_watermark" value="1" @checked(old('apply_watermark', '1') == '1') aria-describedby="productWatermarkHelp">
    <label class="form-check-label" for="productWatermark">Chèn watermark vào ảnh tải lên</label>
    @error('apply_watermark') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
  <div id="productWatermarkHelp" class="form-text">Bỏ chọn để lưu ảnh không chèn watermark. Ảnh vẫn được tối ưu kích thước và chuyển sang WebP. Khi sửa sản phẩm, lựa chọn này chỉ áp dụng cho ảnh mới tải lên.</div>
</div>
