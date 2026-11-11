@extends('layouts.app')
@section('title', 'Organization profile')
@section('content')
<div class="page-head"><div><div class="eyebrow">First-time setup</div><h1>Your organization</h1><p>Tell us which student group is making requests under this account.</p></div></div>
<section class="surface" style="max-width:760px">
    <h2 class="section-title">Student profile</h2>
    @if($errors->any())<div class="alert errors" role="alert">Please correct the highlighted fields.</div>@endif
    <form method="post" action="{{ route('profile.update') }}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PUT">
        <div class="form-grid">
            <div class="field full"><label for="organization">Organization or institute council</label><input id="organization" name="organization" class="{{ $errors->has('organization') ? 'invalid' : '' }}" value="{{ old('organization', $user->organization) }}" required maxlength="160" placeholder="e.g. Student Government"><small class="error">@error('organization'){{ $message }}@enderror</small></div>
            <div class="field full"><label for="position">Position</label><input id="position" name="position" class="{{ $errors->has('position') ? 'invalid' : '' }}" value="{{ old('position', $user->position) }}" required maxlength="120" placeholder="e.g. Organization President"><small class="error">@error('position'){{ $message }}@enderror</small></div>
        </div><div class="actions" style="margin-top:18px"><button type="submit">Save profile</button></div>
    </form>
</section>
@endsection