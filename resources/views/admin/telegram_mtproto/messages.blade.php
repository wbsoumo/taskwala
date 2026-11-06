@extends('layouts.admin')

@section('title', 'Telegram Scraped Messages & Deals')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="fas fa-stream text-primary mr-1"></i> Telegram Scraped Messages & Deals Feed
                </h3>
                <a href="{{ route('admin.telegram_mtproto.index') }}" class="btn btn-outline-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Channels
                </a>
            </div>

            <!-- FILTERS -->
            <div class="card-body bg-light border-bottom p-3">
                <form action="{{ route('admin.telegram_mtproto.messages') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-4 col-12 mb-2 mb-md-0">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search message text or links..." value="{{ request('search') }}">
                    </div>

                    <div class="col-md-3 col-6 mb-2 mb-md-0">
                        <select name="channel_id" class="form-control form-control-sm">
                            <option value="">All Channels</option>
                            @foreach($channels as $c)
                                <option value="{{ $c->channel_id }}" {{ request('channel_id') == $c->channel_id ? 'selected' : '' }}>
                                    {{ $c->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 col-6 mb-2 mb-md-0">
                        <select name="deals_only" class="form-control form-control-sm">
                            <option value="">All Messages</option>
                            <option value="1" {{ request('deals_only') === '1' ? 'selected' : '' }}>Deals Only</option>
                        </select>
                    </div>

                    <div class="col-md-2 col-12 text-right">
                        <button type="submit" class="btn btn-primary btn-sm font-weight-bold mr-1">
                            <i class="fas fa-filter mr-1"></i> Filter
                        </button>
                        @if(request()->hasAny(['search', 'channel_id', 'deals_only']))
                            <a href="{{ route('admin.telegram_mtproto.messages') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- MESSAGES LIST -->
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="bg-light small">
                        <tr>
                            <th style="width: 130px;">Channel</th>
                            <th style="width: 80px;">Media</th>
                            <th>Message Content</th>
                            <th style="width: 140px;">Extracted Deal</th>
                            <th style="width: 130px;">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($messages as $msg)
                            <tr>
                                <td>
                                    <div class="font-weight-bold text-dark small text-truncate" style="max-width: 120px;" title="{{ $msg->channel->title ?? $msg->channel_id }}">
                                        {{ $msg->channel->title ?? 'ID: ' . $msg->channel_id }}
                                    </div>
                                    <span class="badge badge-light border text-muted" style="font-size: 10px;">Msg #{{ $msg->message_id }}</span>
                                    @if($msg->is_edited)
                                        <span class="badge badge-warning text-dark" style="font-size: 9px;">Edited</span>
                                    @endif
                                </td>
                                <td>
                                    @if($msg->media_local_path)
                                        <a href="{{ asset('storage/' . $msg->media_local_path) }}" target="_blank">
                                            <img src="{{ asset('storage/' . $msg->media_local_path) }}" class="rounded border" style="width: 48px; height: 48px; object-fit: cover;">
                                        </a>
                                    @elseif($msg->media_type && $msg->media_type !== 'none')
                                        <span class="badge badge-secondary">{{ ucfirst($msg->media_type) }}</span>
                                    @else
                                        <span class="text-muted small">No Media</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small text-dark text-break" style="max-height: 80px; overflow-y: auto;">
                                        {{ \Illuminate\Support\Str::limit($msg->text, 220) }}
                                    </div>
                                    @if(!empty($msg->extracted_urls))
                                        <div class="mt-1">
                                            @foreach(array_slice($msg->extracted_urls, 0, 2) as $u)
                                                <a href="{{ $u }}" target="_blank" class="badge badge-light border text-truncate d-inline-block text-primary" style="max-width: 250px;">
                                                    <i class="fas fa-external-link-alt fa-xs mr-1"></i> {{ $u }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($msg->deal)
                                        <span class="badge badge-success d-block mb-1">
                                            <i class="fas fa-tag mr-1"></i> Deal Linked
                                        </span>
                                        @if($msg->deal->price)
                                            <div class="font-weight-bold text-success small">
                                                ₹{{ number_format($msg->deal->price, 2) }}
                                                @if($msg->deal->discount_percent)
                                                    <span class="badge badge-danger ml-1" style="font-size: 9px;">{{ $msg->deal->discount_percent }}% OFF</span>
                                                @endif
                                            </div>
                                        @endif
                                        <a href="{{ route('admin.deals.edit', $msg->deal->id) }}" class="small font-weight-bold text-primary">
                                            Edit Deal &rarr;
                                        </a>
                                    @elseif($msg->is_deal)
                                        <span class="badge badge-warning text-dark">Detected as Deal</span>
                                    @else
                                        <span class="text-muted small">Standard Post</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    {{ $msg->posted_at->diffForHumans() }}
                                    <div style="font-size: 10px;">{{ $msg->posted_at->format('M d, H:i') }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 text-secondary d-block"></i>
                                    <strong>No messages extracted yet.</strong><br>
                                    Start channel monitoring or sync past history to import messages.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($messages->hasPages())
                <div class="card-footer bg-light py-2">
                    {{ $messages->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
