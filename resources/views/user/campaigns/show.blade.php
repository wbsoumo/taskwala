@extends('layouts.user')

@section('title', $campaign->name)

@section('content')

<div class="row justify-content-center">
    <div class="col-md-9 col-lg-8">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-bullhorn text-primary mr-2"></i> {{ $campaign->name }}
                </h3>
                <a href="{{ route('user.campaigns.index') }}" class="btn btn-xs btn-default border">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Campaigns
                </a>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="badge badge-info px-3 py-1 text-uppercase">{{ $campaign->category }}</span>
                    <span class="badge badge-success px-2 py-1 ml-2"><i class="fas fa-check-circle mr-1"></i> Active</span>
                </div>

                <p class="text-muted leading-relaxed mb-4">{{ $campaign->short_description }}</p>

                <div class="card bg-light border mb-4">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 border-right">
                                <span class="text-muted small font-weight-bold text-uppercase d-block mb-1">Max Allocated Payout</span>
                                <h2 class="text-success font-weight-bold mb-0">₹{{ number_format($allocatedPayout, 2) }}</h2>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small font-weight-bold text-uppercase d-block mb-1">Conversion Event</span>
                                <h4 class="font-weight-bold text-dark mb-0 mt-1">{{ ucfirst(str_replace('_', ' ', $campaign->conversion_event)) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>

                @if($campaign->terms)
                    <div class="mb-4">
                        <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-gavel text-warning mr-2"></i> Rules & Terms</h6>
                        <div class="p-3 bg-white border rounded text-muted small">
                            {!! nl2br(e($campaign->terms)) !!}
                        </div>
                    </div>
                @endif

                @if($campaign->kpi_requirements)
                    <div class="mb-4">
                        <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-tasks text-info mr-2"></i> KPI Requirements</h6>
                        <div class="p-3 bg-white border rounded text-muted small">
                            {!! nl2br(e($campaign->kpi_requirements)) !!}
                        </div>
                    </div>
                @endif

                <div class="text-right">
                    <a href="{{ route('user.links.generator', ['campaign_id' => $campaign->id]) }}" class="btn btn-primary font-weight-bold shadow-sm px-4 py-2">
                        <i class="fas fa-magic mr-2"></i> Generate Custom Tracking Link
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
