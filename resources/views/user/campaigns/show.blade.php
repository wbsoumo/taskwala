@extends('layouts.user')

@section('title', $campaign->name)

@section('content')

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge badge-primary px-3 py-1 rounded-pill">{{ $campaign->category }}</span>
                <a href="{{ route('user.campaigns.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="fas fa-arrow-left mr-1"></i> Back</a>
            </div>

            <h3 class="font-weight-bold mb-2">{{ $campaign->name }}</h3>
            <p class="text-muted mb-4">{{ $campaign->short_description }}</p>

            <div class="bg-light p-4 rounded-lg mb-4 border">
                <div class="row text-center">
                    <div class="col-6 border-right">
                        <span class="text-muted small font-weight-bold d-block mb-1">YOUR MAX ALLOCATED PAYOUT</span>
                        <h2 class="text-success font-weight-bold mb-0">₹{{ number_format($allocatedPayout, 2) }}</h2>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small font-weight-bold d-block mb-1">CONVERSION EVENT</span>
                        <h5 class="font-weight-bold text-dark mb-0 mt-2">{{ ucfirst(str_replace('_', ' ', $campaign->conversion_event)) }}</h5>
                    </div>
                </div>
            </div>

            @if($campaign->terms)
                <h6 class="font-weight-bold text-dark">Campaign Rules & Terms</h6>
                <div class="bg-white p-3 rounded border text-muted small mb-4">
                    {!! nl2br(e($campaign->terms)) !!}
                </div>
            @endif

            @if($campaign->kpi_requirements)
                <h6 class="font-weight-bold text-dark">KPI Requirements</h6>
                <div class="bg-white p-3 rounded border text-muted small mb-4">
                    {!! nl2br(e($campaign->kpi_requirements)) !!}
                </div>
            @endif

            <div class="text-right">
                <a href="{{ route('user.links.generator', ['campaign_id' => $campaign->id]) }}" class="btn btn-primary btn-lg rounded-pill font-weight-bold px-4">
                    <i class="fas fa-magic mr-2"></i> Create Custom Tracking Link
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
