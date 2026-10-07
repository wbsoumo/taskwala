@extends('layouts.user')

@section('title', 'My Campaign Links')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="font-weight-bold text-dark mb-1">My Generated Campaign Links</h4>
        <p class="text-muted small mb-0">Copy links, inspect payout splits, and monitor traffic.</p>
    </div>
    <a href="{{ route('user.links.generator') }}" class="btn btn-primary rounded-pill font-weight-bold px-4"><i class="fas fa-plus mr-1"></i> Generate New Link</a>
</div>

<div class="card card-custom border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-muted small bg-light">
                        <th>CAMPAIGN</th>
                        <th>PUBLIC URL</th>
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
                            <td class="font-weight-bold">{{ $link->campaign->name ?? 'N/A' }}</td>
                            <td>
                                <div class="input-group input-group-sm" style="max-width: 250px;">
                                    <input type="text" class="form-control" value="{{ $link->public_url }}" readonly id="link_{{ $link->id }}">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-primary btn-copy" type="button" onclick="copyLink('link_{{ $link->id }}')"><i class="fas fa-copy"></i></button>
                                    </div>
                                </div>
                            </td>
                            <td class="text-info font-weight-bold">₹{{ number_format($link->customer_payout, 2) }}</td>
                            <td class="text-success font-weight-bold">₹{{ number_format($link->affiliate_commission, 2) }}</td>
                            <td><span class="badge badge-light border">{{ $link->click_count }}</span></td>
                            <td><span class="badge badge-primary">{{ $link->conversion_count }}</span></td>
                            <td><span class="badge badge-{{ $link->status === 'active' ? 'success' : 'secondary' }} rounded-pill px-3">{{ ucfirst($link->status) }}</span></td>
                            <td>
                                @if($link->status === 'active')
                                    <form action="{{ route('user.links.disable', $link->public_id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-danger rounded-pill px-2">Disable</button>
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
    </div>
    <div class="card-footer bg-white border-0 py-3">
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
        alert("Public link copied to clipboard!");
    }
</script>
@endpush
