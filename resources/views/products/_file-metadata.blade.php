<fieldset class="border rounded p-3 mb-4" aria-describedby="productFileMetadataHelp">
  <legend class="float-none w-auto px-2 fs-5">Thông tin file (không bắt buộc)</legend>
  <p id="productFileMetadataHelp" class="form-text mt-0">Chỉ nhập thông tin file đã biết; để trống nếu chưa rõ. Không trường nào bắt buộc.</p>
  <div class="row g-3">
    <div class="col-md-6">
      <label for="productFileFormat" class="form-label">Định dạng file</label>
      <input type="text" class="form-control @error('file_format') is-invalid @enderror" id="productFileFormat" name="file_format" value="{{ old('file_format', $product->file_format ?? '') }}" maxlength="100" placeholder=".jdp, .art, .stl" aria-describedby="productFileFormatHelp @error('file_format') productFileFormatError @enderror" @error('file_format') aria-invalid="true" @enderror>
      <div id="productFileFormatHelp" class="form-text">Ví dụ: .jdp, .art, .stl. Tối đa 100 ký tự.</div>
      @error('file_format') <div id="productFileFormatError" class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
      <label for="productCompatibleSoftware" class="form-label">Phần mềm phù hợp</label>
      <input type="text" class="form-control @error('compatible_software') is-invalid @enderror" id="productCompatibleSoftware" name="compatible_software" value="{{ old('compatible_software', $product->compatible_software ?? '') }}" maxlength="255" aria-describedby="productCompatibleSoftwareHelp @error('compatible_software') productCompatibleSoftwareError @enderror" @error('compatible_software') aria-invalid="true" @enderror>
      <div id="productCompatibleSoftwareHelp" class="form-text">Chỉ ghi phần mềm đã biết là phù hợp với file. Tối đa 255 ký tự.</div>
      @error('compatible_software') <div id="productCompatibleSoftwareError" class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
      <label for="productFileSize" class="form-label">Dung lượng file</label>
      <input type="text" class="form-control @error('file_size') is-invalid @enderror" id="productFileSize" name="file_size" value="{{ old('file_size', $product->file_size ?? '') }}" maxlength="100" placeholder="25 MB" aria-describedby="productFileSizeHelp @error('file_size') productFileSizeError @enderror" @error('file_size') aria-invalid="true" @enderror>
      <div id="productFileSizeHelp" class="form-text">Nhập dung lượng dễ đọc kèm đơn vị, ví dụ: 25 MB. Tối đa 100 ký tự.</div>
      @error('file_size') <div id="productFileSizeError" class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
      <label for="productDesignDimensions" class="form-label">Kích thước thiết kế</label>
      <input type="text" class="form-control @error('design_dimensions') is-invalid @enderror" id="productDesignDimensions" name="design_dimensions" value="{{ old('design_dimensions', $product->design_dimensions ?? '') }}" maxlength="255" placeholder="1200 × 600 mm" aria-describedby="productDesignDimensionsHelp @error('design_dimensions') productDesignDimensionsError @enderror" @error('design_dimensions') aria-invalid="true" @enderror>
      <div id="productDesignDimensionsHelp" class="form-text">Ghi kèm đơn vị, ví dụ: 1200 × 600 mm. Tối đa 255 ký tự.</div>
      @error('design_dimensions') <div id="productDesignDimensionsError" class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
      <label for="productDownloadNote" class="form-label">Ghi chú tải file</label>
      <textarea class="form-control @error('download_note') is-invalid @enderror" id="productDownloadNote" name="download_note" rows="3" maxlength="500" aria-describedby="productDownloadNoteHelp @error('download_note') productDownloadNoteError @enderror" @error('download_note') aria-invalid="true" @enderror>{{ old('download_note', $product->download_note ?? '') }}</textarea>
      <div id="productDownloadNoteHelp" class="form-text">Không bắt buộc. Nếu đã biết, giải thích link tải miễn phí cung cấp file đầy đủ hay chỉ là file mẫu. Tối đa 500 ký tự.</div>
      @error('download_note') <div id="productDownloadNoteError" class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
  </div>
</fieldset>
