@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <section class="page-header">
        <div>
            <h1>Dashboard</h1>
            <p>Overview of quiz availability, participation, leaderboard movement, and recent admin activity.</p>
        </div>
        <div class="button-row">
            <a class="button button-light" href="{{ route('admin.help.howto') }}">How To</a>
            <a class="button" href="{{ route('admin.quizzes.create') }}">Create quiz</a>
            <a class="button button-light" href="{{ route('admin.reports.index') }}">View reports</a>
        </div>
    </section>

    <section class="panel info-panel compact-panel">
        <div class="panel-header">
            <h2>Getting Trivia Live</h2>
            <a href="{{ route('admin.help.howto') }}">Read guide</a>
        </div>
        <div class="content-list">
            <p>If today's quiz shows up in the API as <code>available: false</code> and <code>state: not_open</code>, the usual reasons are:</p>
            <ul>
                <li>the quiz is still <strong>scheduled</strong> and has not been <strong>published</strong></li>
                <li>the current time is outside the open/close window</li>
                <li>the quiz does not yet meet publish rules</li>
            </ul>
        </div>
    </section>

    <section class="stats-grid">
        <article class="stat-card">
            <span class="stat-label">Today's quiz</span>
            <strong>{{ $overview['today_quiz']['status'] ?? 'none' }}</strong>
            <p>{{ $overview['today_quiz']['title'] ?? 'No quiz scheduled for today.' }}</p>
        </article>
        <article class="stat-card">
            <span class="stat-label">Attempts today</span>
            <strong>{{ $overview['attempts_today'] ?? 0 }}</strong>
            <p>Submitted and in-progress attempts for today's quiz.</p>
        </article>
        <article class="stat-card">
            <span class="stat-label">Average score</span>
            <strong>{{ $overview['average_score_today'] ?? '0.00' }}</strong>
            <p>Average score across submitted attempts today.</p>
        </article>
        <article class="stat-card">
            <span class="stat-label">Pending drafts</span>
            <strong>{{ $overview['pending_draft_quizzes'] ?? 0 }}</strong>
            <p>Draft or scheduled quizzes waiting on publication.</p>
        </article>
    </section>

    <section class="panel-grid two-up">
        <article class="panel">
            <div class="panel-header">
                <h2>Recent quizzes</h2>
                <a href="{{ route('admin.quizzes.index') }}">Manage all</a>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Attempts</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentQuizzes as $quiz)
                        <tr>
                            <td>{{ $quiz->quiz_date?->toDateString() }}</td>
                            <td><a href="{{ route('admin.quizzes.edit', $quiz) }}">{{ $quiz->title }}</a></td>
                            <td>{{ $quiz->status }}</td>
                            <td>{{ $quiz->attempts_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No quizzes yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </article>

        <article class="panel">
            <div class="panel-header">
                <h2>Daily leaderboard</h2>
                <a href="{{ route('admin.reports.index') }}">Open reports</a>
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
                        <tr><td colspan="4">No leaderboard rows yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </article>
    </section>

    <section class="panel-grid two-up">
        <article class="panel">
            <div class="panel-header">
                <h2>Recent attempts</h2>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Status</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentAttempts as $attempt)
                        <tr>
                            <td>{{ $attempt['quiz_date'] }}</td>
                            <td>{{ $attempt['display_name'] ?: $attempt['user_id'] }}</td>
                            <td>{{ $attempt['status'] }}</td>
                            <td>{{ $attempt['score_total'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No attempts recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </article>

        <article class="panel">
            <div class="panel-header">
                <h2>Recent activity</h2>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Reference</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentActivity as $item)
                        <tr>
                            <td>{{ $item['event_name'] }}</td>
                            <td>{{ $item['reference_type'] }} #{{ $item['reference_id'] }}</td>
                            <td>{{ $item['created_at'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">No activity logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </article>
    </section>
@endsection
