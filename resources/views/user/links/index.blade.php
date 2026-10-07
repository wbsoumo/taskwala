@extends('layouts.user')

@section('title', 'My Campaign Links')

@section('content')

<div class="row mb-3 align-items-center">
    <div class="col-sm-6">
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-link text-primary mr-2"></i> My Campaign Links</h4>
    </div>
    <div class="col-sm-6 text-sm-right mt-2 mt-sm-0">
        <a href="{{ route('user.links.generator') }}" class="btn btn-primary font-weight-bold shadow-sm rounded-pill px-3">
            <i class="fas fa-plus mr-1"></i> Generate New Link
        </a>
    </div>
</div>

<div class="card card-outline card-primary shadow-sm">
    <div class="card-body p-0">
        <!-- Desktop Table View -->
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-muted small bg-light">
                        <th>CAMPAIGN</th>
                        <th>TRACKING URL</th>
                        <th>CUSTOMER PAYOUT</th>
                        <th>MY COMMISSION</th>
                        <th>CLICKS</th>
                        <th>CONVERSIONS</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($links as $link)
                        <tr>
                            <td class="font-weight-bold text-dark">{{ $link->campaign->name ?? 'N/A' }}</td>
                            <td>
                                <div class="input-group input-group-sm" style="max-width: 260px;">
                                    <input type="text" class="form-control" value="{{ $link->public_url }}" readonly id="link_{{ $link->id }}">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-primary" type="button" onclick="copyLink('link_{{ $link->id }}')"><i class="fas fa-copy"></i></button>
                                    </div>
                                </div>
                            </td>
                            <td class="text-info font-weight-bold">₹{{ number_format($link->customer_payout, 2) }}</td>
                            <td class="text-success font-weight-bold">₹{{ number_format($link->affiliate_commission, 2) }}</td>
                            <td><span class="badge badge-light border px-2 py-1">{{ $link->click_count }}</span></td>
                            <td><span class="badge badge-primary px-2 py-1">{{ $link->conversion_count }}</span></td>
                            <td><span class="badge badge-{{ $link->status === 'active' ? 'success' : 'secondary' }} px-2 py-1">{{ ucfirst($link->status) }}</span></td>
                            <td>
                                @if($link->status === 'active')
                                    <form action="{{ route('user.links.disable', $link->public_id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-danger font-weight-bold">Disable</button>
                                    </form>
                                @else
                                    <span class="text-muted small">Disabled</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No campaign links generated yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Card Stack View -->
        <div class="d-md-none p-3">
            @forelse($links as $link)
                <div class="card card-outline card-info shadow-sm mb-3">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="font-weight-bold mb-0 text-dark">{{ $link->campaign->name ?? 'N/A' }}</h6>
                        <span class="badge badge-{{ $link->status === 'active' ? 'success' : 'secondary' }} px-2 py-1">{{ ucfirst($link->status) }}</span>
                    </div>
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between my-1 small">
                            <span class="text-muted">Customer Payout:</span>
                            <span class="font-weight-bold text-info">₹{{ number_format($link->customer_payout, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between my-1 small">
                            <span class="text-muted">My Commission:</span>
                            <span class="font-weight-bold text-success">₹{{ number_format($link->affiliate_commission, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between my-1 small">
                            <span class="text-muted">Clicks / Conversions:</span>
                            <span class="font-weight-bold">{{ $link->click_count }} / {{ $link->conversion_count }}</span>
                        </div>
                        <div class="mt-2">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" value="{{ $link->public_url }}" readonly id="mob_link_{{ $link->id }}">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="button" onclick="copyLink('mob_link_{{ $link->id }}')"><i class="fas fa-copy"></i> Copy</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($link->status === 'active')
                        <div class="card-footer bg-white text-right py-2">
                            <form action="{{ route('user.links.disable', $link->public_id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-outline-danger">Disable Link</button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center text-muted py-4">No campaign links generated yet.</div>
            @endforelse
        </div>
    </div>
    <div class="card-footer bg-white border-0">
        {{ $links->links() }}
    </div>
</div>

@endsection

@push('scripts')
<script>
    function copyLink(elementId) {
        const copyText = document.getElementById(elementId);
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value);
        alert("Tracking link copied to clipboard!");
    }
</script>
@endpush
