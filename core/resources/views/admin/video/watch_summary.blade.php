@extends('admin.layouts.app')
@section('panel')
<div class="row">
    <div class="col-lg-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ __('Mobile-app watch summary') }}</h5>
                <form method="GET" class="form-inline">
                    <div class="input-group input-group-sm">
                        <select name="days" class="form-control form-select" onchange="this.form.submit()">
                            @foreach ([7, 30, 90, 180] as $days)
                                <option value="{{ $days }}" @selected($payload['window_days'] === $days)>
                                    {{ $days }} {{ __('days') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <small class="text-muted">{{ __('Sessions') }}</small>
                            <div class="h3 mb-0">{{ number_format($payload['sessions']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <small class="text-muted">{{ __('Completion rate') }}</small>
                            <div class="h3 mb-0">{{ number_format($payload['completion_rate'] * 100, 1) }}%</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <small class="text-muted">{{ __('p50 watched') }}</small>
                            <div class="h3 mb-0">{{ $payload['p50'] }}s</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3">
                            <small class="text-muted">{{ __('p90 watched') }}</small>
                            <div class="h3 mb-0">{{ $payload['p90'] }}s</div>
                        </div>
                    </div>
                </div>

                <h6>{{ __('Drop-off distribution') }}</h6>

                @php
                    $maxBucket = max(1, max($payload['dropoff'] ?: [1]));
                @endphp

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 110px;">{{ __('Bucket') }}</th>
                                <th>{{ __('Sessions') }}</th>
                                <th style="width: 80px;" class="text-end">{{ __('Count') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payload['dropoff'] as $bucket => $count)
                                <tr>
                                    <td><code>{{ $bucket }}</code></td>
                                    <td>
                                        <div class="progress" style="height: 18px;">
                                            <div class="progress-bar bg--success"
                                                 role="progressbar"
                                                 style="width: {{ ($count / $maxBucket) * 100 }}%;">
                                                {{ $count }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">{{ $count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="text-muted small mt-3 mb-0">
                    {{ __('Source: app_events table populated by the mobile player. Web playback is not counted here.') }}
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
