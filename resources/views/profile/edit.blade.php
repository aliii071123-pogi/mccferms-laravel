@extends('layouts.app')
@section('title', 'Organization profile')
@section('content')
@php
    $currentOrganizationIsCustom = $user->organization && ! $organizations->contains('name', $user->organization);
    $selectedOrganization = old('organization', $currentOrganizationIsCustom ? '__other__' : $user->organization);
@endphp
<div class="page-head"><div><div class="eyebrow">First-time setup</div><h1>Your organization</h1><p>Tell us which student group is making requests under this account.</p></div></div>
<section class="surface" style="max-width:760px">
    <h2 class="section-title">Student profile</h2>
    @if($errors->any())<div class="alert errors" role="alert">Please correct the highlighted fields.</div>@endif
    <form method="post" action="{{ route('profile.update') }}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PUT">
        <div class="form-grid">
            <div class="field full"><label for="organization">Organization or institute council</label><select id="organization" name="organization" class="{{ $errors->has('organization') ? 'invalid' : '' }}" required><option value="">Choose your organization</option>@foreach($organizations as $organization)<option value="{{ $organization->name }}" @selected($selectedOrganization === $organization->name)>{{ $organization->name }}</option>@endforeach<option value="__other__" @selected($selectedOrganization === '__other__')>Other (not listed)</option></select><small class="error">@error('organization'){{ $message }}@enderror</small></div>
            <div class="field full" id="other-organization-field" @if($selectedOrganization !== '__other__') hidden @endif><label for="other_organization">Enter your organization or institute council</label><input id="other_organization" name="other_organization" class="{{ $errors->has('other_organization') ? 'invalid' : '' }}" value="{{ old('other_organization', $currentOrganizationIsCustom ? $user->organization : '') }}" maxlength="160" @if($selectedOrganization === '__other__') required @endif><small class="error">@error('other_organization'){{ $message }}@enderror</small></div>
            <div class="field full"><label for="position">Position</label><input id="position" name="position" class="{{ $errors->has('position') ? 'invalid' : '' }}" value="{{ old('position', $user->position) }}" required maxlength="120" placeholder="e.g. Organization President"><small class="error">@error('position'){{ $message }}@enderror</small></div>
        </div><div class="actions" style="margin-top:18px"><button type="submit">Save profile</button></div>
    </form>
</section>
@endsection
@push('scripts')
<script>
    const organizationSelect = document.getElementById('organization');
    const otherOrganizationField = document.getElementById('other-organization-field');
    const otherOrganizationInput = document.getElementById('other_organization');

    organizationSelect.addEventListener('change', () => {
        const isOther = organizationSelect.value === '__other__';
        otherOrganizationField.hidden = !isOther;
        otherOrganizationInput.required = isOther;
        if (!isOther) {
            otherOrganizationInput.value = '';
        }
    });
</script>
@endpush