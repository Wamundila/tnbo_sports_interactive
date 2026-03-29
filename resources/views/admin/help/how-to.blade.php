@extends('layouts.admin')

@section('title', 'How To')

@section('content')
    <section class="page-header">
        <div>
            <h1>How To Get Trivia Live</h1>
            <p>Quick guidance for creating a quiz that actually becomes playable on <code>/api/v1/trivia/today</code>.</p>
        </div>
        <div class="button-row">
            <a class="button" href="{{ route('admin.quizzes.create') }}">Create quiz</a>
            <a class="button button-light" href="{{ route('admin.quizzes.index') }}">Open quizzes</a>
        </div>
    </section>

    <section class="panel">
        <div class="panel-header">
            <h2>Why You May See <code>available: false</code> And <code>state: not_open</code></h2>
        </div>
        <div class="content-list">
            <p>A quiz can exist for today and still be unavailable. The most common reason is that the quiz is only <strong>scheduled</strong>, not <strong>published</strong>.</p>
            <p>In this system, <strong>scheduled</strong> means you are still preparing the quiz. It is not playable yet.</p>
            <p>For <code>/api/v1/trivia/today</code> to return a playable state, the quiz must be:</p>
            <ul>
                <li>for today's date</li>
                <li><strong>published</strong></li>
                <li>within the <code>opens_at</code> and <code>closes_at</code> window</li>
                <li>fully valid for publish</li>
            </ul>
        </div>
    </section>

    <section class="panel">
        <div class="panel-header">
            <h2>Checklist</h2>
        </div>
        <ol class="steps-list">
            <li>Create or open the quiz for today's date.</li>
            <li>Set a title, description, open time, and close time.</li>
            <li>Make sure <code>opens_at</code> is before <code>closes_at</code>.</li>
            <li>Set the expected question count. For current trivia flow, this is usually <strong>3</strong>.</li>
            <li>Add exactly that many active questions.</li>
            <li>For each active question, add exactly <strong>3</strong> options.</li>
            <li>For each active question, mark exactly <strong>1</strong> option as correct.</li>
            <li>Save the quiz.</li>
            <li>Click <strong>Publish</strong>.</li>
            <li>Confirm the current time is after <code>opens_at</code> and before <code>closes_at</code>.</li>
        </ol>
    </section>

    <section class="panel-grid two-up">
        <article class="panel">
            <div class="panel-header">
                <h2>Status Meaning</h2>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Meaning</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>draft</strong></td>
                        <td>Still being edited. Not playable.</td>
                    </tr>
                    <tr>
                        <td><strong>scheduled</strong></td>
                        <td>Prepared, but still not live. You still need to publish it.</td>
                    </tr>
                    <tr>
                        <td><strong>published</strong></td>
                        <td>Eligible to go live if the current time is within the open/close window and the quiz is valid.</td>
                    </tr>
                    <tr>
                        <td><strong>closed</strong></td>
                        <td>No longer playable.</td>
                    </tr>
                    <tr>
                        <td><strong>archived</strong></td>
                        <td>Kept for record/history. Not playable.</td>
                    </tr>
                </tbody>
            </table>
        </article>

        <article class="panel">
            <div class="panel-header">
                <h2>State Mapping On <code>/today</code></h2>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>State</th>
                        <th>Typical reason</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>available</strong></td>
                        <td>Quiz is published, valid, and currently open.</td>
                    </tr>
                    <tr>
                        <td><strong>in_progress</strong></td>
                        <td>User already started and the attempt has not expired.</td>
                    </tr>
                    <tr>
                        <td><strong>already_played</strong></td>
                        <td>User already submitted today's quiz.</td>
                    </tr>
                    <tr>
                        <td><strong>not_open</strong></td>
                        <td>Quiz exists, but it is still scheduled, invalid for publish, or the open time has not arrived yet.</td>
                    </tr>
                    <tr>
                        <td><strong>closed</strong></td>
                        <td>Quiz close time passed or the quiz was manually closed.</td>
                    </tr>
                    <tr>
                        <td><strong>no_quiz</strong></td>
                        <td>No quiz exists for today's date.</td>
                    </tr>
                    <tr>
                        <td><strong>verification_required</strong></td>
                        <td>The quiz is available, but the user account is not verified.</td>
                    </tr>
                </tbody>
            </table>
        </article>
    </section>

    <section class="panel note-panel">
        <div class="panel-header">
            <h2>Fastest Path To A Live Quiz</h2>
        </div>
        <div class="content-list">
            <p>If you just want to get a test quiz live quickly:</p>
            <ul>
                <li>set today's date</li>
                <li>set <code>status</code> to <strong>scheduled</strong> while editing</li>
                <li>set <code>opens_at</code> to a few minutes before now</li>
                <li>set <code>closes_at</code> to later today</li>
                <li>make sure all 3 questions are active and complete</li>
                <li>save</li>
                <li>click <strong>Publish</strong></li>
            </ul>
            <p>After that, <code>/api/v1/trivia/today</code> should move from <code>not_open</code> to <code>available</code> for eligible users.</p>
        </div>
    </section>
@endsection
