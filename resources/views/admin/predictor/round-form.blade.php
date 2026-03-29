@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
    <section class="page-header">
        <div>
            <h1>{{ $pageTitle }}</h1>
            <p>Rounds become playable only when they are <code>open</code>, have fixtures, and the current time falls between <code>opens_at</code> and <code>prediction_closes_at</code>.</p>
        </div>
        <div class="button-row">
            <a href="{{ route('admin.predictor.seasons.edit', $season) }}" class="button button-light">Back To Season</a>
        </div>
    </section>

    @if ($round)
        <section class="panel-grid two-up">
            <article class="panel compact-panel">
                <div class="panel-header">
                    <h2>Quick State Actions</h2>
                </div>
                <div class="button-row wrap-row">
                    @foreach (['draft' => 'Move To Draft', 'open' => 'Open Round', 'locked' => 'Lock Picks', 'cancelled' => 'Cancel Round'] as $status => $label)
                        <form method="POST" action="{{ route('admin.predictor.rounds.transition', $round) }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ $status }}">
                            <button type="submit" class="button button-light button-small">{{ $label }}</button>
                        </form>
                    @endforeach
                </div>
            </article>

            <article class="panel compact-panel">
                <div class="panel-header">
                    <h2>Scoring Actions</h2>
                </div>
                <div class="button-row wrap-row">
                    <form method="POST" action="{{ route('admin.predictor.rounds.score', $round) }}">
                        @csrf
                        <button type="submit" class="button button-small">Score Round</button>
                    </form>
                    <form method="POST" action="{{ route('admin.predictor.rounds.recalculate', $round) }}">
                        @csrf
                        <button type="submit" class="button button-light button-small">Recalculate Round</button>
                    </form>
                </div>
                <p class="muted">Scoring requires every fixture to be finalized as <code>completed</code>, <code>postponed</code>, or <code>cancelled</code>. The score action marks the round completed and refreshes round, monthly, season, and all-time leaderboards.</p>
            </article>
        </section>
    @endif

    <form method="POST" action="{{ $round ? route('admin.predictor.rounds.update', $round) : route('admin.predictor.seasons.rounds.store', $season) }}" class="stack-lg">
        @csrf
        @if ($round)
            @method('PUT')
        @endif

        <section class="panel stack-md">
            <div class="panel-header">
                <h2>Round Settings</h2>
            </div>

            <div class="form-grid two-columns">
                <div>
                    <label for="name">Round Name</label>
                    <input id="name" name="name" value="{{ old('name', $form['name']) }}" required>
                </div>
                <div>
                    <label for="round_number">Round Number</label>
                    <input id="round_number" type="number" min="1" name="round_number" value="{{ old('round_number', $form['round_number']) }}">
                </div>
                <div>
                    <label for="opens_at">Opens At</label>
                    <input id="opens_at" type="datetime-local" name="opens_at" value="{{ old('opens_at', $form['opens_at']) }}" required>
                </div>
                <div>
                    <label for="prediction_closes_at">Prediction Closes At</label>
                    <input id="prediction_closes_at" type="datetime-local" name="prediction_closes_at" value="{{ old('prediction_closes_at', $form['prediction_closes_at']) }}" required>
                </div>
                <div>
                    <label for="round_closes_at">Round Closes At</label>
                    <input id="round_closes_at" type="datetime-local" name="round_closes_at" value="{{ old('round_closes_at', $form['round_closes_at']) }}" required>
                </div>
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        @foreach (['draft', 'open', 'locked', 'scoring', 'completed', 'cancelled'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $form['status']) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="span-2 checkbox-row checkbox-tall">
                    <input id="allow_partial_submission" type="checkbox" name="allow_partial_submission" value="1" @checked(old('allow_partial_submission', $form['allow_partial_submission']))>
                    <label for="allow_partial_submission">Allow partial submissions for this round</label>
                </div>
                <div class="span-2">
                    <label for="notes">Admin Notes</label>
                    <textarea id="notes" name="notes">{{ old('notes', $form['notes']) }}</textarea>
                </div>
            </div>
        </section>

        <section class="panel stack-md">
            <div class="panel-header">
                <h2>Fixtures</h2>
            </div>

            <div class="stack-md">
                @foreach ($form['fixtures'] as $index => $fixture)
                    <article class="fixture-card">
                        <div class="fixture-card-header">
                            <h3>Fixture {{ $index + 1 }}</h3>
                            <span class="muted">Display order {{ old("fixtures.$index.display_order", $fixture['display_order']) }}</span>
                        </div>

                        <input type="hidden" name="fixtures[{{ $index }}][id]" value="{{ old("fixtures.$index.id", $fixture['id']) }}">
                        <input type="hidden" name="fixtures[{{ $index }}][display_order]" value="{{ old("fixtures.$index.display_order", $fixture['display_order']) }}">

                        <div class="form-grid three-columns">
                            <div>
                                <label for="fixture-{{ $index }}-competition_name_snapshot">Competition</label>
                                <input id="fixture-{{ $index }}-competition_name_snapshot" name="fixtures[{{ $index }}][competition_name_snapshot]" value="{{ old("fixtures.$index.competition_name_snapshot", $fixture['competition_name_snapshot']) }}">
                            </div>
                            <div>
                                <label for="fixture-{{ $index }}-source_fixture_id">Source Fixture ID</label>
                                <input id="fixture-{{ $index }}-source_fixture_id" type="number" min="1" name="fixtures[{{ $index }}][source_fixture_id]" value="{{ old("fixtures.$index.source_fixture_id", $fixture['source_fixture_id']) }}">
                            </div>
                            <div>
                                <label for="fixture-{{ $index }}-competition_id">Competition ID</label>
                                <input id="fixture-{{ $index }}-competition_id" type="number" min="1" name="fixtures[{{ $index }}][competition_id]" value="{{ old("fixtures.$index.competition_id", $fixture['competition_id']) }}">
                            </div>
                            <div>
                                <label for="fixture-{{ $index }}-home_team_name_snapshot">Home Team</label>
                                <input id="fixture-{{ $index }}-home_team_name_snapshot" name="fixtures[{{ $index }}][home_team_name_snapshot]" value="{{ old("fixtures.$index.home_team_name_snapshot", $fixture['home_team_name_snapshot']) }}">
                            </div>
                            <div>
                                <label for="fixture-{{ $index }}-away_team_name_snapshot">Away Team</label>
                                <input id="fixture-{{ $index }}-away_team_name_snapshot" name="fixtures[{{ $index }}][away_team_name_snapshot]" value="{{ old("fixtures.$index.away_team_name_snapshot", $fixture['away_team_name_snapshot']) }}">
                            </div>
                            <div>
                                <label for="fixture-{{ $index }}-kickoff_at">Kickoff At</label>
                                <input id="fixture-{{ $index }}-kickoff_at" type="datetime-local" name="fixtures[{{ $index }}][kickoff_at]" value="{{ old("fixtures.$index.kickoff_at", $fixture['kickoff_at']) }}">
                            </div>
                            <div>
                                <label for="fixture-{{ $index }}-home_team_id">Home Team ID</label>
                                <input id="fixture-{{ $index }}-home_team_id" type="number" min="1" name="fixtures[{{ $index }}][home_team_id]" value="{{ old("fixtures.$index.home_team_id", $fixture['home_team_id']) }}">
                            </div>
                            <div>
                                <label for="fixture-{{ $index }}-away_team_id">Away Team ID</label>
                                <input id="fixture-{{ $index }}-away_team_id" type="number" min="1" name="fixtures[{{ $index }}][away_team_id]" value="{{ old("fixtures.$index.away_team_id", $fixture['away_team_id']) }}">
                            </div>
                            <div>
                                <label for="fixture-{{ $index }}-result_status">Result Status</label>
                                <select id="fixture-{{ $index }}-result_status" name="fixtures[{{ $index }}][result_status]">
                                    @foreach (['pending', 'live', 'completed', 'postponed', 'cancelled'] as $resultStatus)
                                        <option value="{{ $resultStatus }}" @selected(old("fixtures.$index.result_status", $fixture['result_status']) === $resultStatus)>{{ ucfirst($resultStatus) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="fixture-{{ $index }}-actual_home_score">Actual Home Score</label>
                                <input id="fixture-{{ $index }}-actual_home_score" type="number" min="0" name="fixtures[{{ $index }}][actual_home_score]" value="{{ old("fixtures.$index.actual_home_score", $fixture['actual_home_score']) }}">
                            </div>
                            <div>
                                <label for="fixture-{{ $index }}-actual_away_score">Actual Away Score</label>
                                <input id="fixture-{{ $index }}-actual_away_score" type="number" min="0" name="fixtures[{{ $index }}][actual_away_score]" value="{{ old("fixtures.$index.actual_away_score", $fixture['actual_away_score']) }}">
                            </div>
                            <div>
                                <label for="fixture-{{ $index }}-result_source">Result Source</label>
                                <input id="fixture-{{ $index }}-result_source" name="fixtures[{{ $index }}][result_source]" value="{{ old("fixtures.$index.result_source", $fixture['result_source']) }}">
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="button-row">
                <button type="submit" class="button">{{ $round ? 'Save Round' : 'Create Round' }}</button>
            </div>
        </section>
    </form>
@endsection
