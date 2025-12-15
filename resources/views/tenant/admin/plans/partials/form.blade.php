@php
    $isEdit = !empty($plan);
@endphp

<div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text"
           name="name"
           class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $plan->name ?? '') }}"
           required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Description (optional)</label>
    <textarea name="description"
              class="form-control @error('description') is-invalid @enderror"
              rows="3">{{ old('description', $plan->description ?? '') }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <label class="form-label">Amount (cents)</label>
        <input type="number"
               name="amount"
               min="100"
               step="1"
               class="form-control @error('amount') is-invalid @enderror"
               value="{{ old('amount', $plan->amount ?? 999) }}"
               required>
        <div class="form-text">Example: 999 = $9.99</div>
        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Currency</label>
        <input type="text"
               name="currency"
               class="form-control @error('currency') is-invalid @enderror"
               value="{{ old('currency', $plan->currency ?? 'usd') }}"
               required>
        <div class="form-text">3-letter code (usd)</div>
        @error('currency') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Interval</label>
        <select name="interval" class="form-select @error('interval') is-invalid @enderror" required>
            @php $val = old('interval', $plan->interval ?? 'month'); @endphp
            @foreach(['day','week','month','year'] as $i)
                <option value="{{ $i }}" @selected($val === $i)>{{ ucfirst($i) }}</option>
            @endforeach
        </select>
        @error('interval') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="active" value="1"
                   id="active"
                   @checked(old('active', $plan->active ?? true))>
            <label class="form-check-label" for="active">Active</label>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="featured" value="1"
                   id="featured"
                   @checked(old('featured', $plan->featured ?? false))>
            <label class="form-check-label" for="featured">Featured</label>
        </div>
    </div>
</div>
