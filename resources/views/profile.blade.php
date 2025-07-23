@extends('adminlte::page')

@section('title', 'My Profile')

@section('content_header')
    <h1><i class="fas fa-user-edit mr-2"></i>My Profile</h1>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            {{-- User Profile Picture and Basic Info --}}
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        {{-- REPLACED broken image tag with a robust Font Awesome icon --}}
                        <i class="fas fa-user-circle profile-user-icon"></i>
                    </div>

                    <h3 class="profile-username text-center">{{ $user->name }}</h3>

                    <p class="text-muted text-center">Member since {{ $user->created_at->format('M. Y') }}</p>

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item">
                            <b>Email</b>
                            <span class="float-right text-muted">{{ $user->email }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            {{-- Profile Update Form --}}
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Update Your Information</h3>
                </div>
                <div class="card-body">
                    {{-- Display Success Message --}}
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible">
                             <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="icon fas fa-check"></i> {{ session('success') }}
                        </div>
                    @endif

                    {{-- Display Validation Errors --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        
                        {{-- 
                            FIX: Add the user's email as a hidden field.
                            This ensures the backend validation rule 'email => required' is satisfied
                            when you only want to update the name.
                        --}}
                        <input type="hidden" name="email" value="{{ $user->email }}">

                        {{-- Name Field --}}
                        <div class="form-group">
                            <label for="name">Display Name</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                            </div>
                            @error('name')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Read-only Email Field --}}
                        <div class="form-group">
                             <label for="email_display">Email Address</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                </div>
                                <input type="email" class="form-control" id="email_display" value="{{ $user->email }}" readonly>
                            </div>
                        </div>

                        {{-- Informational Alert --}}
                        <div class="alert alert-info mt-4">
                            <i class="icon fas fa-info-circle"></i>
                            For security reasons, email and password changes require administrator assistance. Please contact support if needed.
                        </div>

                        <div class="form-group mt-4 text-right">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save mr-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .profile-user-icon {
        font-size: 120px;
        color: #ced4da;
    }

    /* Make alerts look a bit softer */
    .alert {
        border-radius: .25rem;
    }

    /* Ensure card headers have consistent font weight */
    .card-header .card-title {
        font-weight: 600;
    }
</style>
@stop

@section('js')
<script>
    console.log("Profile Page Loaded!");
</script>
@stop