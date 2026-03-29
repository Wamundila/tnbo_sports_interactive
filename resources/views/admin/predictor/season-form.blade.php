@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
    <section class="page-header">
        <div>
            <h1>{{ $pageTitle }}</h1>
            <p>Season settings define the active scoring rules and the season used for current-rank calculations.</p>
        </div>
        <div class="button-row">
            <a href="{{ route('admin.predictor.campaigns.edit', $campaign) }}" class="button button-light">Back To Campaign</a>
            @if ($season)
                <a href="{{ route('admin.predictor.seasons.rounds.create', $season) }}" class="button">Add Round</a>
            @endif
        </div>
    </section>

    <form method="POST" action="{{ $season ? route('admin.predictor.seasons.update', $season) : route('admin.predictor.campaigns.seasons.store', $campaign) }}" class="stack-lg">
        @csrf
        @if ($season)
            @method('PUT')
        @endif

        <section class="panel stack-md">
            <div class="panel-header">
                <h2>Season Settings</h2>
            </div>

            <div class="form-grid two-columns">
                <div>
                    <label for="name">Season Name</label>
                    <input id="name" name="name" value="{{ old('name', $form['name']) }}" required>
                </div>
                <div>
                    <label for="slug">Season Slug</label>
                    <input id="slug" name="slug" value="{{ old('slug', $form['slug']) }}" required>
                </div>
                <div>
                    <label for="start_date">Start Date</label>
                    <input id="start_date" type="date" name="start_date" value="{{ old('start_date', $form['start_date']) }}" required>
                </div>
                <div>
                    <label for="end_date">End Date</label>
                    <input id="end_date" type="date" name="end_date" value="{{ old('end_date', $form['end_date']) }}" required>
                </div>
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        @foreach (['draft', 'active', 'completed', 'archived'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $form['status']) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="checkbox-row checkbox-tall">
                    <input id="is_current" type="checkbox" name="is_current" value="1" @checked(old('is_current', $form['is_current']))>
                    <label for="is_current">Mark as current season for this campaign</label>
                </div>
                <div>
                    <label for="scoring_outcome_points">Outcome Points</label>
                    <input id="scoring_outcome_points" type="number" step="0.01" min="0" name="scoring_outcome_points" value="{{ old('scoring_outcome_points', $form['scoring_outcome_points']) }}" required>
                </div>
                <div>
                    <label for="scoring_exact_score_points">Exact Score Points</label>
                    <input id="scoring_exact_score_points" type="number" step="0.01" min="0" name="scoring_exact_score_points" value="{{ old('scoring_exact_score_points', $form['scoring_exact_score_points']) }}" required>
                </div>
                <div>
                    <label for="scoring_close_score_points">Close Score Points</label>
                    <input id="scoring_close_score_points" type="number" step="0.01" min="0" name="scoring_close_score_points" value="{{ old('scoring_close_score_points', $form['scoring_close_score_points']) }}" required>
                </div>
                <div>
                    <label for="scoring_banker_multiplier">Banker Multiplier</label>
                    <input id="scoring_banker_multiplier" type="number" step="0.01" min="1" name="scoring_banker_multiplier" value="{{ old('scoring_banker_multiplier', $form['scoring_banker_multiplier']) }}" required>
                </div>
                <div class="span-2">
                    <label for="rules_text">Rules Text</label>
                    <textarea id="rules_text" name="rules_text">{{ old('rules_text', $form['rules_text']) }}</textarea>
                </div>
            </div>

            <div class="button-row">
                <button type="submit" class="button">{{ $season ? 'Save Season' : 'Create Season' }}</button>
            </div>
        </section>
    </form>

    @if ($season)
        <section class="panel-grid two-up">
            <article class="panel info-panel">
                <div class="panel-header">
                    <h2>Season Notes</h2>
                </div>
                <ul class="steps-list compact-list">
                    <li>This season is {{ $season->is_current ? 'currently used' : 'not current yet' }} for campaign leaderboard rank lookups.</li>
                    <li>Rounds under this season inherit its scoring configuration.</li>
                    <li>Change the current-season flag carefully because predictor summary and performance endpoints will follow it.</li>
                </ul>
            </article>

            <article class="panel note-panel">
                <div class="panel-header">
                    <h2>Banker Picks</h2>
                </div>
                <p class="muted">Campaign banker setting is <strong>{{ $form['banker_enabled'] ? 'enabled' : 'disabled' }}</strong>. If disabled, user submissions with banker selections will be rejected regardless of season scoring values.</p>
            </article>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2>Rounds</h2>
                <a href="{{ route('admin.predictor.seasons.rounds.create', $season) }}" class="button button-light button-small">Create Round</a>
            </div>

            @if ($rounds->isEmpty())
                <p class="muted">No rounds exist for this season yet.</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Round</th>
                            <th>Status</th>
                            <th>Fixtures</th>
                            <th>Window</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rounds as $round)
                            <tr>
                                <td>
                                    <strong>{{ $round->name }}</strong>
                                    <div class="muted">Round #{{ $round->round_number ?? 'n/a' }}</div>
                                </td>
                                <td><span class="status-badge status-{{ $round->status }}">{{ ucfirst($round->status) }}</span></td>
                                <td>{{ $round->fixtures_count }}</td>
                                <td>
                                    <div>{{ $round->opens_at?->format('d M Y H:i') }}</div>
                                    <div class="muted">closes {{ $round->prediction_closes_at?->format('d M Y H:i') }}</div>
                                </td>
                                <td>
                                    <a href="{{ route('admin.predictor.rounds.edit', $round) }}" class="button button-light button-small">Manage</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @endif
@endsection
