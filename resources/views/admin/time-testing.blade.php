@extends('adminlte::page')

@section('title', 'Time Testing Utility')

@section('content_header')
    <h1><i class="fas fa-clock mr-2"></i>Time Testing Utility</h1>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Set Application Time</h3>
                </div>
                <div class="card-body">
                    @if(!app()->environment('local', 'testing'))
                        <div class="alert alert-danger">
                            <strong>Warning!</strong> Time testing is disabled because the application is not in a 'local' or 'testing' environment.
                        </div>
                    @else
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
