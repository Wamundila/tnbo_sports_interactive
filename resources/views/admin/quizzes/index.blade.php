@extends('layouts.admin')

@section('title', 'Quizzes')

@section('content')
    <section class="page-header">
        <div>
            <h1>Quizzes</h1>
            <p>Manage draft, scheduled, published, and closed TNBO trivia quizzes.</p>
        </div>
        <a class="button" href="{{ route('admin.quizzes.create') }}">Create quiz</a>
    </section>

    <section class="panel compact-panel">
        <form method="GET" action="{{ route('admin.quizzes.index') }}" class="filter-row">
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All statuses</option>
                    @foreach (['draft', 'scheduled', 'published', 'closed', 'archived'] as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="button-row align-end">
                <button type="submit" class="button button-light">Apply filter</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Questions</th>
                    <th>Attempts</th>
                    <th>Avg score</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($quizzes as $quiz)
                    <tr>
                        <td>{{ $quiz->quiz_date?->toDateString() }}</td>
                        <td><a href="{{ route('admin.quizzes.edit', $quiz) }}">{{ $quiz->title }}</a></td>
                        <td>{{ $quiz->status }}</td>
                        <td>{{ $quiz->questions_count }}</td>
                        <td>{{ $quiz->attempts_count }}</td>
                        <td>{{ $quiz->attempts_avg_score_total !== null ? number_format((float) $quiz->attempts_avg_score_total, 2) : 'n/a' }}</td>
                        <td>
                            <div class="button-stack">
                                <a class="button button-small button-light" href="{{ route('admin.quizzes.edit', $quiz) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.quizzes.publish', $quiz) }}">
                                    @csrf
                                    <button type="submit" class="button button-small">Publish</button>
                                </form>
                                <form method="POST" action="{{ route('admin.quizzes.close', $quiz) }}">
                                    @csrf
                                    <button type="submit" class="button button-small button-danger">Close</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">No quizzes found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination-wrap">
            {{ $quizzes->links() }}
        </div>
    </section>
@endsection
