@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
    <section class="page-header">
        <div>
            <h1>Reports</h1>
            <p>Attempts, leaderboard snapshots, and audit activity for the trivia service.</p>
        </div>
    </section>

    <section class="panel compact-panel">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="form-grid three-columns">
            <div>
                <label for="quiz_date">Quiz date</label>
                <input id="quiz_date" name="quiz_date" type="date" value="{{ $filters['quiz_date'] }}">
            </div>
            <div>
                <label for="status">Attempt status</label>
                <select id="status" name="status">
                    <option value="">Any status</option>
                    @foreach (['started', 'submitted', 'expired'] as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="client">Client type</label>
                <input id="client" name="client" type="text" value="{{ $filters['client'] }}">
            </div>
            <div>
                <label for="min_score">Min score</label>
                <input id="min_score" name="min_score" type="number" min="0" value="{{ $filters['min_score'] }}">
            </div>
            <div>
                <label for="max_score">Max score</label>
                <input id="max_score" name="max_score" type="number" min="0" value="{{ $filters['max_score'] }}">
            </div>
            <div>
                <label for="limit">Attempt limit</label>
                <input id="limit" name="limit" type="number" min="1" max="100" value="{{ $filters['limit'] }}">
            </div>
            <div>
                <label for="board_type">Leaderboard type</label>
                <select id="board_type" name="board_type">
                    @foreach (['daily', 'weekly', 'monthly', 'all_time'] as $boardType)
                        <option value="{{ $boardType }}" @selected($filters['board_type'] === $boardType)>{{ ucfirst(str_replace('_', ' ', $boardType)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="period_key">Period key</label>
                <input id="period_key" name="period_key" type="text" value="{{ $filters['period_key'] }}" placeholder="Optional override">
            </div>
            <div class="button-row align-end">
                <button type="submit" class="button">Apply filters</button>
            </div>
        </form>
    </section>

    <section class="panel-grid two-up">
        <article class="panel">
            <div class="panel-header">
                <h2>Attempts</h2>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Status</th>
                        <th>Score</th>
                        <th>Client</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attempts as $attempt)
                        <tr>
                            <td>{{ $attempt['quiz_date'] }}</td>
                            <td>{{ $attempt['display_name'] ?: $attempt['user_id'] }}</td>
                            <td>{{ $attempt['status'] }}</td>
                            <td>{{ $attempt['score_total'] }}</td>
                            <td>{{ $attempt['client_type'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No attempts matched the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </article>

        <article class="panel">
            <div class="panel-header">
                <h2>{{ ucfirst(str_replace('_', ' ', $leaderboard['board_type'])) }} leaderboard</h2>
                <span class="muted">Period: {{ $leaderboard['period_key'] }}</span>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>User</th>
                        <th>Points</th>
                        <th>Accuracy</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leaderboard['entries'] as $entry)
                        <tr>
                            <td>{{ $entry['rank'] }}</td>
                            <td>{{ $entry['user']['display_name'] ?: $entry['user']['user_id'] }}</td>
                            <td>{{ $entry['points'] }}</td>
                            <td>{{ number_format($entry['accuracy'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No leaderboard rows available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </article>
    </section>

    <section class="panel">
        <div class="panel-header">
            <h2>Recent activity</h2>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Actor</th>
                    <th>Reference</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activity as $item)
                    <tr>
                        <td>{{ $item['event_name'] }}</td>
                        <td>{{ $item['actor_type'] }} {{ $item['actor_id'] ? '#' . $item['actor_id'] : '' }}</td>
                        <td>{{ $item['reference_type'] }} #{{ $item['reference_id'] }}</td>
                        <td>{{ $item['created_at'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No activity entries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
