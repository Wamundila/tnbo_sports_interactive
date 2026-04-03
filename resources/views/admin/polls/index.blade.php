@extends('layouts.admin')

@section('title', 'Single Choice Polls')

@section('content')
    <div class="page-header">
        <div>
            <div class="eyebrow">Single Choice Poll</div>
            <h1>Polls</h1>
            <p>Create, schedule, publish, and close opinion or award-style polls for TNBO Sports.</p>
        </div>
        <a href="{{ route('admin.polls.create') }}" class="button">New Poll</a>
    </div>

    <div class="panel compact-panel">
        <form method="GET" class="filter-row wrap-row">
            <div>
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="">All statuses</option>
                    @foreach (['draft', 'scheduled', 'live', 'closed', 'archived'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="button-row align-end">
                <button type="submit" class="button button-light">Apply</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <table class="table">
            <thead>
                <tr>
                    <th>Poll</th>
                    <th>Status</th>
                    <th>Timing</th>
                    <th>Options</th>
                    <th>Votes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($polls as $poll)
                    <tr>
                        <td>
                            <strong>{{ $poll->title }}</strong><br>
                            <span class="muted">{{ $poll->slug }}</span>
                        </td>
                        <td><span class="status-badge status-{{ $poll->status }}">{{ ucfirst($poll->status) }}</span></td>
                        <td>
                            <div><strong>Open:</strong> {{ $poll->open_at?->format('Y-m-d H:i') ?? 'Immediate' }}</div>
                            <div><strong>Close:</strong> {{ $poll->close_at?->format('Y-m-d H:i') ?? 'Manual' }}</div>
                        </td>
                        <td>{{ $poll->options_count }}</td>
                        <td>{{ $poll->votes_count }}</td>
                        <td><a href="{{ route('admin.polls.edit', $poll) }}" class="button button-light button-small">Manage</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No polls created yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination-wrap">
            {{ $polls->links() }}
        </div>
    </div>
@endsection
