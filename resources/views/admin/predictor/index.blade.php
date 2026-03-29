@extends('layouts.admin')

@section('title', 'Predictor Campaigns')

@section('content')
    <section class="page-header">
        <div>
            <h1>Predictor Campaigns</h1>
            <p>Manage campaign visibility, current seasons, and live rounds from one admin area. User-facing predictor only becomes truly live when campaign, season, and round states all align.</p>
        </div>
        <a href="{{ route('admin.predictor.campaigns.create') }}" class="button">Create Campaign</a>
    </section>

    <section class="panel-grid two-up">
        <article class="panel info-panel">
            <div class="panel-header">
                <h2>How To Get Predictor Live</h2>
            </div>
            <ol class="steps-list compact-list">
                <li>Create a public campaign and set it to <code>active</code>.</li>
                <li>Create a season, mark it current, and set it to <code>active</code>.</li>
                <li>Create a round with fixtures, then move the round to <code>open</code>.</li>
                <li>Make sure <code>opens_at</code> is already in the past and <code>prediction_closes_at</code> is still in the future.</li>
                <li>Use <code>/api/v1/predictor/summary</code> or <code>/current-round</code> to confirm the user surface is available.</li>
            </ol>
        </article>

        <article class="panel note-panel">
            <div class="panel-header">
                <h2>Current User API Surface</h2>
            </div>
            <div class="content-list">
                <ul>
                    <li><code>GET /api/v1/predictor/campaigns</code></li>
                    <li><code>GET /api/v1/predictor/summary?campaign_slug=...</code></li>
                    <li><code>GET /api/v1/predictor/campaigns/{campaign}/current-round</code></li>
                    <li><code>POST /api/v1/predictor/rounds/{round}/draft</code></li>
                    <li><code>POST /api/v1/predictor/rounds/{round}/submit</code></li>
                </ul>
            </div>
        </article>
    </section>

    <section class="panel compact-panel">
        <form method="GET" class="filter-row">
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All statuses</option>
                    @foreach (['draft', 'active', 'archived'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="button-row align-end">
                <button type="submit" class="button button-light">Filter</button>
                <a href="{{ route('admin.predictor.index') }}" class="button button-light">Reset</a>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="panel-header">
            <h2>Campaign List</h2>
        </div>

        @if ($campaigns->isEmpty())
            <p class="muted">No predictor campaigns exist yet.</p>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Status</th>
                        <th>Visibility</th>
                        <th>Seasons</th>
                        <th>Fixture Target</th>
                        <th>Window</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($campaigns as $campaign)
                        <tr>
                            <td>
                                <strong>{{ $campaign->display_name }}</strong>
                                <div class="muted">{{ $campaign->slug }}</div>
                            </td>
                            <td><span class="status-badge status-{{ $campaign->status }}">{{ ucfirst($campaign->status) }}</span></td>
                            <td>{{ ucfirst($campaign->visibility) }}</td>
                            <td>{{ $campaign->seasons_count }}</td>
                            <td>{{ $campaign->default_fixture_count }}</td>
                            <td>
                                <div>{{ $campaign->starts_at?->format('d M Y H:i') ?? 'Not set' }}</div>
                                <div class="muted">to {{ $campaign->ends_at?->format('d M Y H:i') ?? 'Open ended' }}</div>
                            </td>
                            <td>
                                <a href="{{ route('admin.predictor.campaigns.edit', $campaign) }}" class="button button-light button-small">Manage</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="pagination-wrap">
                {{ $campaigns->links() }}
            </div>
        @endif
    </section>
@endsection
