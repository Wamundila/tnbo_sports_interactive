@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
    <section class="page-header">
        <div>
            <h1>{{ $pageTitle }}</h1>
            <p>Campaign status and visibility determine whether the predictor can appear on Games or Home surfaces.</p>
        </div>
        <div class="button-row">
            <a href="{{ route('admin.predictor.index') }}" class="button button-light">Back To Campaigns</a>
            @if ($campaign)
                <a href="{{ route('admin.predictor.campaigns.seasons.create', $campaign) }}" class="button">Add Season</a>
            @endif
        </div>
    </section>

    <form method="POST" action="{{ $campaign ? route('admin.predictor.campaigns.update', $campaign) : route('admin.predictor.campaigns.store') }}" class="stack-lg">
        @csrf
        @if ($campaign)
            @method('PUT')
        @endif

        <section class="panel stack-md">
            <div class="panel-header">
                <h2>Campaign Settings</h2>
            </div>

            <div class="form-grid two-columns">
                <div>
                    <label for="name">Internal Name</label>
                    <input id="name" name="name" value="{{ old('name', $form['name']) }}" required>
                </div>
                <div>
                    <label for="slug">Slug</label>
                    <input id="slug" name="slug" value="{{ old('slug', $form['slug']) }}" required>
                </div>
                <div>
                    <label for="display_name">Display Name</label>
                    <input id="display_name" name="display_name" value="{{ old('display_name', $form['display_name']) }}" required>
                </div>
                <div>
                    <label for="sponsor_name">Sponsor Name</label>
                    <input id="sponsor_name" name="sponsor_name" value="{{ old('sponsor_name', $form['sponsor_name']) }}">
                </div>
                <div>
                    <label for="scope_type">Scope Type</label>
                    <select id="scope_type" name="scope_type">
                        @foreach (['single_competition', 'multi_competition', 'curated'] as $scope)
                            <option value="{{ $scope }}" @selected(old('scope_type', $form['scope_type']) === $scope)>{{ str_replace('_', ' ', ucfirst($scope)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="default_fixture_count">Default Fixture Count</label>
                    <input id="default_fixture_count" type="number" min="1" max="50" name="default_fixture_count" value="{{ old('default_fixture_count', $form['default_fixture_count']) }}" required>
                </div>
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        @foreach (['draft', 'active', 'archived'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $form['status']) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="visibility">Visibility</label>
                    <select id="visibility" name="visibility">
                        @foreach (['public', 'private'] as $visibility)
                            <option value="{{ $visibility }}" @selected(old('visibility', $form['visibility']) === $visibility)>{{ ucfirst($visibility) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="starts_at">Starts At</label>
                    <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', $form['starts_at']) }}">
                </div>
                <div>
                    <label for="ends_at">Ends At</label>
                    <input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at', $form['ends_at']) }}">
                </div>
                <div class="span-2">
                    <label for="description">Description</label>
                    <textarea id="description" name="description">{{ old('description', $form['description']) }}</textarea>
                </div>
            </div>

            <div class="checkbox-row">
                <input id="banker_enabled" type="checkbox" name="banker_enabled" value="1" @checked(old('banker_enabled', $form['banker_enabled']))>
                <label for="banker_enabled">Enable banker picks for this campaign</label>
            </div>

            <div class="button-row">
                <button type="submit" class="button">{{ $campaign ? 'Save Campaign' : 'Create Campaign' }}</button>
            </div>
        </section>
    </form>

    @if ($campaign)
        <section class="panel-grid two-up">
            <article class="panel info-panel">
                <div class="panel-header">
                    <h2>Live Checklist</h2>
                </div>
                <ol class="steps-list compact-list">
                    <li>Set campaign to <code>active</code> and <code>public</code>.</li>
                    <li>Create at least one current season under this campaign.</li>
                    <li>Create a round with fixtures and set that round to <code>open</code>.</li>
                    <li>Use a past <code>opens_at</code> and a future <code>prediction_closes_at</code> if the round should be playable immediately.</li>
                </ol>
            </article>

            <article class="panel note-panel">
                <div class="panel-header">
                    <h2>Integration Note</h2>
                </div>
                <p class="muted">BFF should consume predictor through <code>/api/v1/predictor/summary?campaign_slug={{ $campaign->slug }}</code> and related protected endpoints. Flutter should not call Interactive directly.</p>
            </article>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2>Seasons</h2>
                <a href="{{ route('admin.predictor.campaigns.seasons.create', $campaign) }}" class="button button-light button-small">Create Season</a>
            </div>

            @if ($seasons->isEmpty())
                <p class="muted">No seasons exist for this campaign yet.</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Season</th>
                            <th>Status</th>
                            <th>Current</th>
                            <th>Rounds</th>
                            <th>Date Range</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($seasons as $season)
                            <tr>
                                <td>
                                    <strong>{{ $season->name }}</strong>
                                    <div class="muted">{{ $season->slug }}</div>
                                </td>
                                <td><span class="status-badge status-{{ $season->status }}">{{ ucfirst($season->status) }}</span></td>
                                <td>{{ $season->is_current ? 'Yes' : 'No' }}</td>
                                <td>{{ $season->rounds_count }}</td>
                                <td>
                                    <div>{{ $season->start_date?->format('d M Y') }}</div>
                                    <div class="muted">to {{ $season->end_date?->format('d M Y') }}</div>
                                </td>
                                <td>
                                    <a href="{{ route('admin.predictor.seasons.edit', $season) }}" class="button button-light button-small">Manage</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @endif
@endsection
