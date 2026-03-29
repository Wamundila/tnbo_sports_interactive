@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
    <section class="page-header">
        <div>
            <h1>{{ $pageTitle }}</h1>
            <p>Quiz editing is intentionally simple: standard Blade forms, fixed server validation, and no front-end build step.</p>
        </div>
        <div class="button-row">
            <a class="button button-light" href="{{ route('admin.help.howto') }}">How To</a>
            <a class="button button-light" href="{{ route('admin.quizzes.index') }}">Back to quizzes</a>
            <a class="button button-light" href="{{ route('admin.reports.index') }}">View reports</a>
        </div>
    </section>

    @php
        $quizDate = old('quiz_date', $form['quiz_date']);
        $title = old('title', $form['title']);
        $shortDescription = old('short_description', $form['short_description']);
        $status = old('status', $form['status']);
        $opensAt = old('opens_at', $form['opens_at']);
        $closesAt = old('closes_at', $form['closes_at']);
        $questionCountExpected = old('question_count_expected', $form['question_count_expected']);
        $timePerQuestion = old('time_per_question_seconds', $form['time_per_question_seconds']);
        $pointsPerCorrect = old('points_per_correct', $form['points_per_correct']);
        $sportSlug = old('sport_slug', $form['sport_slug']);
        $streakBonusEnabled = old('streak_bonus_enabled', $form['streak_bonus_enabled']) ? true : false;
    @endphp

    <section class="panel info-panel compact-panel">
        <div class="panel-header">
            <h2>Before You Expect Trivia To Go Live</h2>
            <a href="{{ route('admin.help.howto') }}">Open full guide</a>
        </div>
        <div class="content-list">
            <p><strong>Scheduled</strong> is not live. A quiz becomes playable only after you click <strong>Publish</strong>.</p>
            <ul>
                <li>Quiz date must be today.</li>
                <li>Current time must be between <code>opens_at</code> and <code>closes_at</code>.</li>
                <li>The quiz must have the expected number of active questions.</li>
                <li>Each active question must have exactly 3 options.</li>
                <li>Each active question must have exactly 1 correct option.</li>
            </ul>
            <p>If any of those conditions are not met, <code>/api/v1/trivia/today</code> can still return <code>available: false</code> with <code>state: not_open</code>.</p>
        </div>
    </section>

    <section class="panel">
        <form method="POST" action="{{ $quiz ? route('admin.quizzes.update', $quiz) : route('admin.quizzes.store') }}" class="stack-lg">
            @csrf
            @if ($quiz)
                @method('PUT')
            @endif

            <div class="form-grid two-columns">
                <div>
                    <label for="quiz_date">Quiz date</label>
                    <input id="quiz_date" name="quiz_date" type="date" value="{{ $quizDate }}" required>
                </div>
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        @foreach (['draft', 'scheduled', 'archived'] as $statusOption)
                            <option value="{{ $statusOption }}" @selected($status === $statusOption)>{{ ucfirst($statusOption) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="span-2">
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" value="{{ $title }}" required>
                </div>
                <div class="span-2">
                    <label for="short_description">Short description</label>
                    <textarea id="short_description" name="short_description" rows="3">{{ $shortDescription }}</textarea>
                </div>
                <div>
                    <label for="opens_at">Opens at</label>
                    <input id="opens_at" name="opens_at" type="datetime-local" value="{{ $opensAt }}">
                </div>
                <div>
                    <label for="closes_at">Closes at</label>
                    <input id="closes_at" name="closes_at" type="datetime-local" value="{{ $closesAt }}">
                </div>
                <div>
                    <label for="question_count_expected">Question count</label>
                    <input id="question_count_expected" name="question_count_expected" type="number" min="1" max="10" value="{{ $questionCountExpected }}">
                </div>
                <div>
                    <label for="sport_slug">Sport slug</label>
                    <input id="sport_slug" name="sport_slug" type="text" value="{{ $sportSlug }}">
                </div>
                <div>
                    <label for="time_per_question_seconds">Time per question (seconds)</label>
                    <input id="time_per_question_seconds" name="time_per_question_seconds" type="number" min="1" max="300" value="{{ $timePerQuestion }}">
                </div>
                <div>
                    <label for="points_per_correct">Points per correct</label>
                    <input id="points_per_correct" name="points_per_correct" type="number" min="1" max="100" value="{{ $pointsPerCorrect }}">
                </div>
            </div>

            <div class="checkbox-row">
                <input type="hidden" name="streak_bonus_enabled" value="0">
                <input id="streak_bonus_enabled" name="streak_bonus_enabled" type="checkbox" value="1" @checked($streakBonusEnabled)>
                <label for="streak_bonus_enabled">Enable streak bonus scoring</label>
            </div>

            @foreach ($form['questions'] as $questionIndex => $question)
                @php
                    $questionPrefix = 'questions.' . $questionIndex;
                    $questionId = old($questionPrefix . '.id', $question['id']);
                    $questionText = old($questionPrefix . '.question_text', $question['question_text']);
                    $imageUrl = old($questionPrefix . '.image_url', $question['image_url']);
                    $explanationText = old($questionPrefix . '.explanation_text', $question['explanation_text']);
                    $sourceType = old($questionPrefix . '.source_type', $question['source_type']);
                    $sourceRef = old($questionPrefix . '.source_ref', $question['source_ref']);
                    $difficulty = old($questionPrefix . '.difficulty', $question['difficulty']);
                    $questionStatus = old($questionPrefix . '.status', $question['status']);
                    $questionSportSlug = old($questionPrefix . '.sport_slug', $question['sport_slug']);
                @endphp

                <section class="question-card">
                    <div class="question-header">
                        <h2>Question {{ $questionIndex + 1 }}</h2>
                        <span>Position {{ $question['position'] }}</span>
                    </div>

                    <input type="hidden" name="questions[{{ $questionIndex }}][id]" value="{{ $questionId }}">
                    <input type="hidden" name="questions[{{ $questionIndex }}][position]" value="{{ $question['position'] }}">

                    <div class="form-grid two-columns">
                        <div class="span-2">
                            <label for="question_{{ $questionIndex }}_text">Question text</label>
                            <input id="question_{{ $questionIndex }}_text" name="questions[{{ $questionIndex }}][question_text]" type="text" value="{{ $questionText }}" required>
                        </div>
                        <div class="span-2">
                            <label for="question_{{ $questionIndex }}_explanation">Explanation</label>
                            <textarea id="question_{{ $questionIndex }}_explanation" name="questions[{{ $questionIndex }}][explanation_text]" rows="2">{{ $explanationText }}</textarea>
                        </div>
                        <div>
                            <label for="question_{{ $questionIndex }}_difficulty">Difficulty</label>
                            <select id="question_{{ $questionIndex }}_difficulty" name="questions[{{ $questionIndex }}][difficulty]">
                                @foreach (['easy', 'medium', 'hard'] as $difficultyOption)
                                    <option value="{{ $difficultyOption }}" @selected($difficulty === $difficultyOption)>{{ ucfirst($difficultyOption) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="question_{{ $questionIndex }}_status">Status</label>
                            <select id="question_{{ $questionIndex }}_status" name="questions[{{ $questionIndex }}][status]">
                                @foreach (['draft', 'active', 'retired'] as $statusOption)
                                    <option value="{{ $statusOption }}" @selected($questionStatus === $statusOption)>{{ ucfirst($statusOption) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="question_{{ $questionIndex }}_image_url">Image URL</label>
                            <input id="question_{{ $questionIndex }}_image_url" name="questions[{{ $questionIndex }}][image_url]" type="url" value="{{ $imageUrl }}">
                        </div>
                        <div>
                            <label for="question_{{ $questionIndex }}_sport_slug">Sport slug</label>
                            <input id="question_{{ $questionIndex }}_sport_slug" name="questions[{{ $questionIndex }}][sport_slug]" type="text" value="{{ $questionSportSlug }}">
                        </div>
                        <div>
                            <label for="question_{{ $questionIndex }}_source_type">Source type</label>
                            <input id="question_{{ $questionIndex }}_source_type" name="questions[{{ $questionIndex }}][source_type]" type="text" value="{{ $sourceType }}">
                        </div>
                        <div>
                            <label for="question_{{ $questionIndex }}_source_ref">Source ref</label>
                            <input id="question_{{ $questionIndex }}_source_ref" name="questions[{{ $questionIndex }}][source_ref]" type="text" value="{{ $sourceRef }}">
                        </div>
                    </div>

                    <div class="options-grid">
                        @foreach ($question['options'] as $optionIndex => $option)
                            @php
                                $optionPrefix = $questionPrefix . '.options.' . $optionIndex;
                                $optionId = old($optionPrefix . '.id', $option['id']);
                                $optionText = old($optionPrefix . '.option_text', $option['option_text']);
                                $isCorrect = old($optionPrefix . '.is_correct', $option['is_correct']) ? true : false;
                            @endphp

                            <article class="option-card">
                                <input type="hidden" name="questions[{{ $questionIndex }}][options][{{ $optionIndex }}][id]" value="{{ $optionId }}">
                                <input type="hidden" name="questions[{{ $questionIndex }}][options][{{ $optionIndex }}][position]" value="{{ $option['position'] }}">
                                <label for="question_{{ $questionIndex }}_option_{{ $optionIndex }}">Option {{ $optionIndex + 1 }}</label>
                                <input id="question_{{ $questionIndex }}_option_{{ $optionIndex }}" name="questions[{{ $questionIndex }}][options][{{ $optionIndex }}][option_text]" type="text" value="{{ $optionText }}" required>
                                <div class="checkbox-row compact">
                                    <input type="hidden" name="questions[{{ $questionIndex }}][options][{{ $optionIndex }}][is_correct]" value="0">
                                    <input id="question_{{ $questionIndex }}_correct_{{ $optionIndex }}" name="questions[{{ $questionIndex }}][options][{{ $optionIndex }}][is_correct]" type="checkbox" value="1" @checked($isCorrect)>
                                    <label for="question_{{ $questionIndex }}_correct_{{ $optionIndex }}">Correct answer</label>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <div class="button-row">
                <button type="submit" class="button">Save quiz</button>
                @if ($quiz)
                    <form method="POST" action="{{ route('admin.quizzes.publish', $quiz) }}">
                        @csrf
                        <button type="submit" class="button button-light">Publish</button>
                    </form>
                    <form method="POST" action="{{ route('admin.quizzes.close', $quiz) }}">
                        @csrf
                        <button type="submit" class="button button-danger">Close</button>
                    </form>
                @endif
            </div>
        </form>
    </section>

    @if ($quiz)
        <section class="panel compact-panel">
            <div class="panel-header">
                <h2>Duplicate quiz</h2>
            </div>
            <form method="POST" action="{{ route('admin.quizzes.duplicate', $quiz) }}" class="form-grid two-columns">
                @csrf
                <div>
                    <label for="duplicate_quiz_date">New quiz date</label>
                    <input id="duplicate_quiz_date" name="quiz_date" type="date" value="{{ old('quiz_date', now()->addDay()->toDateString()) }}" required>
                </div>
                <div>
                    <label for="duplicate_title">New title</label>
                    <input id="duplicate_title" name="title" type="text" value="{{ old('title', $quiz->title . ' Copy') }}">
                </div>
                <div>
                    <label for="duplicate_short_description">Short description</label>
                    <textarea id="duplicate_short_description" name="short_description" rows="2">{{ old('short_description', $quiz->short_description) }}</textarea>
                </div>
                <div>
                    <label for="duplicate_opens_at">Opens at</label>
                    <input id="duplicate_opens_at" name="opens_at" type="datetime-local" value="{{ old('opens_at', $quiz->opens_at?->format('Y-m-d\TH:i')) }}">
                </div>
                <div>
                    <label for="duplicate_closes_at">Closes at</label>
                    <input id="duplicate_closes_at" name="closes_at" type="datetime-local" value="{{ old('closes_at', $quiz->closes_at?->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="button-row align-end">
                    <button type="submit" class="button button-light">Duplicate quiz</button>
                </div>
            </form>
        </section>
    @endif
@endsection
