@extends('adminlte::page')

@section('title', 'Admin Utilities')

@section('content_header')
    <h1><i class="fas fa-cogs mr-2"></i>Admin Utilities</h1>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            {{-- This block will display the output from the settlement command --}}
            @if (session('settle_output'))
                <div class="alert alert-default-secondary">
                    <h5 class="alert-heading">Settlement Output:</h5>
                    <pre class="mb-0" style="white-space: pre-wrap; word-break: break-all;">{{ rtrim(session('settle_output')) }}</pre>
                </div>
            @endif

            {{-- Display session messages for all actions on this page --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="icon fas fa-check"></i> {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="icon fas fa-ban"></i> {{ session('error') }}
                </div>
            @endif
            @if (session('info'))
                <div class="alert alert-info alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="icon fas fa-info"></i> {{ session('info') }}
                </div>
            @endif

            {{-- Prize Pool Management Card -- NEW CARD --}}
            <div class="card card-warning mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-trophy mr-2"></i>Prize Pool Management</h3>
                </div>
                <div class="card-body">
                    <p>Set the total prize pool for the season.</p>
                    <form method="POST" action="{{ route('admin.pool.update') }}">
                        @csrf
                        <div class="form-group">
                            <label for="pool_size"><strong>Current Prize Pool ($)</strong></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="number" id="pool_size" name="pool_size" class="form-control @error('pool_size') is-invalid @enderror" 
                                       value="{{ old('pool_size', $currentPoolSize) }}" 
                                       required min="0" step="any">
                            </div>
                            @error('pool_size')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-2"></i>Update Pool Size</button>
                    </form>
                </div>
            </div>

            {{-- Time Testing Card --}}
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock mr-2"></i>Time Testing Utility</h3>
                </div>
                <div class="card-body">
                    @if(!app()->environment('local', 'testing'))
                        <div class="alert alert-danger">
                            <strong>Warning!</strong> Time testing is disabled because the application is not in a 'local' or 'testing' environment.
                        </div>
                    @else
                        <div class="alert alert-info">
                            <h5 class="alert-heading">Current Effective Time</h5>
                            <p class="mb-0">{!! $currentTimeMessage !!}</p>
                        </div>

                        <p class="mt-4">Setting a time here will override the application's clock for betting logic. This only works in local/testing environments and only affects your current browser session.</p>

                        <form method="POST" action="{{ route('admin.time.set') }}" class="mt-4 border-top pt-3">
                            @csrf
                            <div class="form-group">
                                <label for="test_time"><strong>Set New Test Time</strong> (in your local timezone)</label>
                                <input type="datetime-local" id="test_time" name="test_time" class="form-control" required>
                                <small class="form-text text-muted">The time you pick will be used as the "current time" for all betting calculations.</small>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Set Time</button>
                        </form>
                        
                        <form method="POST" action="{{ route('admin.time.reset') }}" class="mt-3 border-top pt-3">
                            @csrf
                            <label><strong>Reset Time</strong></label>
                            <p>Click here to clear the test time and revert to using the actual server time.</p>
                            <button type="submit" class="btn btn-danger"><i class="fas fa-undo mr-2"></i>Reset to Real Time</button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Manual Bet Settlement Card --}}
            <div class="card card-success mt-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-check-circle mr-2"></i>Manual Bet Settlement</h3>
                </div>
                <div class="card-body">
                    <p>Trigger the weekly bet settlement process manually. Leave the week field blank to process all eligible past weeks according to the command's logic.</p>
                    
                    <form method="POST" action="{{ route('admin.bets.settle') }}">
                        @csrf
                        <div class="form-group">
                            <label for="week"><strong>Optional: Specific Week</strong></label>
                            <input type="text" id="week" name="week" class="form-control" placeholder="YYYY-WW (e.g., {{ \Carbon\Carbon::now()->subWeek()->format('o-W') }})">
                            <small class="form-text text-muted">If you specify a week, only that week will be targeted for settlement.</small>
                        </div>
                        <button type="submit" class="btn btn-success"><i class="fas fa-play-circle mr-2"></i>Settle Bets Now</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const timeInput = document.getElementById('test_time');

        if (timeInput) {
            // Check if a pre-populated time was passed from the controller.
            // The '??' operator provides a fallback to null if the variable isn't set.
            const prepopulatedTime = @json($prepopulatedTime ?? null);

            if (prepopulatedTime) {
                // If a session time exists, use it to set the input's value.
                timeInput.value = prepopulatedTime;
            } else {
                // Otherwise, default to the current real local time.
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                const formattedDateTime = now.toISOString().slice(0, 16);
                timeInput.value = formattedDateTime;
            }
        }
    });
</script>
@stop