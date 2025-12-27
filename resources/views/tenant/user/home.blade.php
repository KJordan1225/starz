@extends('layouts.landlord')

@section('content')

@php
    use App\Services\TenantService;
    $tenantService = new TenantService();
    $tenantId = $tenantService->getTenantId();
@endphp


<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">

            <div class="card shadow-sm">
                <div class="card-header text-center bg-white">
                    <h4 class="mb-0">User Dashboard</h4>
                </div>

                <div class="card-body">

                    <div class="d-grid gap-3">
                        <a href="{{ route('tenant.creator.images.creatorImagePageTwo', ['tenant' => $tenantId]) }}" class="btn btn-outline-primary btn-lg">
                            View Exclusive Images
                        </a>
                        
                        <a href="{{ route('tenant.videos.display', ['tenant' => $tenantId]) }}" class="btn btn-outline-primary btn-lg">
                            View Exclusive Videos
                        </a>

                        <a href="{{ route('tenant.subscriptions.cancel.show', ['tenant' => $tenantId]) }}" class="btn btn-outline-primary btn-lg">
                            Cancel Subscription
                        </a>

                        
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
