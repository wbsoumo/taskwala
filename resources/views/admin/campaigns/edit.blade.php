@extends('layouts.admin')

@section('title', 'Edit Campaign: ' . $campaign->name)

@section('content')

<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-edit text-primary mr-2"></i>Edit Campaign</h3>
            </div>
            <form action="{{ route('admin.campaigns.update', $campaign) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Campaign Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $campaign->name) }}" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Category <span class="text-danger">*</span></label>
                            <input type="text" name="category" class="form-control" value="{{ old('category', $campaign->category) }}" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Campaign Type <span class="text-danger">*</span></label>
                            <select name="campaign_type" class="form-control" required>
                                <option value="cpa" {{ old('campaign_type', $campaign->campaign_type) === 'cpa' ? 'selected' : '' }}>CPA (Cost Per Action)</option>
                                <option value="cpl" {{ old('campaign_type', $campaign->campaign_type) === 'cpl' ? 'selected' : '' }}>CPL (Cost Per Lead)</option>
                                <option value="cps" {{ old('campaign_type', $campaign->campaign_type) === 'cps' ? 'selected' : '' }}>CPS (Cost Per Sale)</option>
                                <option value="cpi" {{ old('campaign_type', $campaign->campaign_type) === 'cpi' ? 'selected' : '' }}>CPI (Cost Per Install)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Upload Logo File (PNG/JPG/WebP/SVG)</label>
                            <div class="custom-file">
                                <input type="file" name="logo_file" class="custom-file-input" id="logoFile" accept="image/*">
                                <label class="custom-file-label" for="logoFile">Choose file...</label>
                            </div>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>OR Logo Image Direct URL</label>
                            <input type="text" name="logo_url" class="form-control" value="{{ old('logo_url', $campaign->logo_url) }}" placeholder="https://domain.com/logo.png">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Public Offer Page Theme <span class="text-danger">*</span></label>
                            <select name="theme" class="form-control" required>
                                <option value="gradient_blue" {{ old('theme', $campaign->theme) === 'gradient_blue' ? 'selected' : '' }}>Vibrant Gradient Blue (Default)</option>
                                <option value="dark_glass" {{ old('theme', $campaign->theme) === 'dark_glass' ? 'selected' : '' }}>Sleek Dark Glassmorphism</option>
                                <option value="emerald" {{ old('theme', $campaign->theme) === 'emerald' ? 'selected' : '' }}>Emerald Fintech Green</option>
                                <option value="clean_minimal" {{ old('theme', $campaign->theme) === 'clean_minimal' ? 'selected' : '' }}>Clean Light Minimalist</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-5 form-group">
                            <label>Advertiser / Network Name <span class="text-danger">*</span></label>
                            <input type="text" name="advertiser_name" class="form-control" value="{{ old('advertiser_name', $campaign->advertiser_name) }}" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Conversion Event <span class="text-danger">*</span></label>
                            <input type="text" name="conversion_event" class="form-control" value="{{ old('conversion_event', $campaign->conversion_event) }}" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Postback Provider</label>
                            <select name="postback_provider_id" class="form-control">
                                <option value="">-- Global / System Default --</option>
                                @foreach($providers as $p)
                                    <option value="{{ $p->id }}" {{ old('postback_provider_id', $campaign->postback_provider_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Landing Target URL (with <code>{click_id}</code> macro) <span class="text-danger">*</span></label>
                        <input type="text" name="landing_url" class="form-control" value="{{ old('landing_url', $campaign->landing_url) }}" required>
                    </div>

                    <div class="row bg-light p-3 rounded mb-3 border">
                        <div class="col-md-5 form-group mb-0">
                            <label class="text-primary font-weight-bold">LEVEL 1: Advertiser Payout (Gross) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                <input type="number" step="0.01" name="advertiser_payout" class="form-control" value="{{ old('advertiser_payout', $campaign->advertiser_payout) }}" required>
                            </div>
                        </div>
                        <div class="col-md-5 form-group mb-0">
                            <label class="text-success font-weight-bold">LEVEL 2: Default Affiliate Payout Allocation <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                <input type="number" step="0.01" name="default_affiliate_payout" class="form-control" value="{{ old('default_affiliate_payout', $campaign->default_affiliate_payout) }}" required>
                            </div>
                        </div>
                        <div class="col-md-2 form-group mb-0">
                            <label>Currency</label>
                            <input type="text" name="currency" class="form-control" value="{{ old('currency', $campaign->currency) }}" required readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control" required>
                                <option value="active" {{ old('status', $campaign->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="draft" {{ old('status', $campaign->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="paused" {{ old('status', $campaign->status) === 'paused' ? 'selected' : '' }}>Paused</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Start Date</label>
                            <input type="datetime-local" name="start_date" class="form-control" value="{{ old('start_date', $campaign->start_date ? $campaign->start_date->format('Y-m-d\TH:i') : '') }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>End Date</label>
                            <input type="datetime-local" name="end_date" class="form-control" value="{{ old('end_date', $campaign->end_date ? $campaign->end_date->format('Y-m-d\TH:i') : '') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Short Description</label>
                        <input type="text" name="short_description" class="form-control" value="{{ old('short_description', $campaign->short_description) }}">
                    </div>

                    <div class="form-group">
                        <label>Full Campaign Terms & Instructions</label>
                        <textarea name="terms" class="form-control" rows="3">{{ old('terms', $campaign->terms) }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>KPI & Qualification Requirements</label>
                        <textarea name="kpi_requirements" class="form-control" rows="2">{{ old('kpi_requirements', $campaign->kpi_requirements) }}</textarea>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <a href="{{ route('admin.campaigns.show', $campaign) }}" class="btn btn-secondary mr-2">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Update Campaign</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $('#logoFile').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName || 'Choose file...');
    });
</script>
@endpush
