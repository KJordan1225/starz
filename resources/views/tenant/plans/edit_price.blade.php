@extends('layouts.landlord')

@section('content')
    <div class="container">
        <h1>Edit Subscription Price for {{ $plan->title }}</h1>

        <form method="POST" action="{{ route('tenant.plans.update_price', ['tenant' => $tenant->id, 'plan' => $plan->id]) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" step="0.01" min="0" name="price" id="price" class="form-control" value="{{ old('price', $plan->price) }}" required>
                @error('price')
                    <div class="alert alert-danger mt-2">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary mt-3">Update Price</button>
        </form>
    </div>
@endsection