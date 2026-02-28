<div class="row g-3">
    <div class="col-md-6">
        <label for="option_name-field" class="form-label">Key <span class="text-muted">(option_name)</span></label>
        <input
            type="text"
            id="option_name-field"
            name="option_name"
            class="form-control @error('option_name') is-invalid @enderror"
            value="{{ old('option_name', $option->option_name) }}"
            placeholder="e.g. domain_template">
        @error('option_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="form-text">A unique identifier for this variable.</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="option_value-field" class="form-label">Value <span class="text-muted">(option_value)</span></label>
        <input
            type="text"
            id="option_value-field"
            name="option_value"
            class="form-control @error('option_value') is-invalid @enderror"
            value="{{ old('option_value', $option->option_value) }}"
            placeholder="e.g. mycompany.test">
        @error('option_value')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="form-text">The value used by the app for this key.</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="order" class="form-label">Order</label>
        <input
            type="number"
            id="order"
            name="order"
            class="form-control @error('order') is-invalid @enderror"
            value="{{ old('order', $option->order) }}"
            placeholder="0">
        @error('order')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="category" class="form-label">Category</label>
        <input
            type="text"
            id="category"
            name="category"
            class="form-control @error('category') is-invalid @enderror"
            value="{{ old('category', $option->category) }}"
            placeholder="e.g. system, ui, server">
        @error('category')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="title" class="form-label">Title</label>
        <input
            type="text"
            id="title"
            name="title"
            class="form-control @error('title') is-invalid @enderror"
            value="{{ old('title', $option->title) }}"
            placeholder="Human-readable label">
        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="version" class="form-label">Version</label>
        <input
            type="text"
            id="version"
            name="version"
            class="form-control @error('version') is-invalid @enderror"
            value="{{ old('version', $option->version) }}"
            placeholder="e.g. 1.0.0">
        @error('version')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
