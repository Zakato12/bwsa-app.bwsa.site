@extends('layouts.main')

@section('title', 'Audit Logs')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Audit Logs</h1>
        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('audit.logs.test') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary">Create Test Log</button>
            </form>
            <span class="text-muted">Latest 200 entries</span>
        </div>
    </div>

    <div class="mb-3">
        <span class="badge {{ $dbLoggingOk ? 'bg-success' : 'bg-danger' }}">DB Log: {{ $dbLoggingOk ? 'OK' : 'FAIL' }}</span>
        <span class="badge {{ $fileLoggingOk ? 'bg-success' : 'bg-danger' }} ms-2">File Log: {{ $fileLoggingOk ? 'OK' : 'FAIL' }}</span>
    </div>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Event</th>
                    <th>Actor</th>
                    <th>Role</th>
                    <th>Context</th>
                    <th>IP</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td>{{ $log->event }}</td>
                        <td>{{ $log->actor_id ?? '-' }}</td>
                        <td>{{ $log->role ?? '-' }}</td>
                        <td>
                            @php
                                $ctx = $log->context ?? null;
                                $decoded = null;
                                if (is_string($ctx)) {
                                    $decoded = json_decode($ctx, true);
                                }
                            @endphp
                            @if(is_array($decoded))
                                @php
                                    $data = $decoded['data'] ?? $decoded;
                                @endphp
                                @if(isset($data['note']))
                                    <span>{{ $data['note'] }}</span>
                                @else
                                    <span>
                                        {{ collect($data)->map(function ($v, $k) {
                                            if (is_array($v)) {
                                                $v = json_encode($v, JSON_UNESCAPED_SLASHES);
                                            }
                                            return "{$k}: {$v}";
                                        })->implode(', ') }}
                                    </span>
                                @endif
                            @elseif($ctx)
                                <span>{{ $ctx }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $log->ip_address ?? '-' }}</td>
                        <td>{{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y h:i A') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No audit logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
