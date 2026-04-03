@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
    <div class="page-header">
        <div>
            <div class="eyebrow">Single Choice Poll</div>
            <h1>{{ $pageTitle }}</h1>
            <p>Poll cards are guest-readable through BFF, but voting follows the TNBO/AuthBox JWT trust model.</p>
        </div>
        <div class="button-row wrap-row">
            <a href="{{ route('admin.polls.index') }}" class="button button-light">Back to Polls</a>
            @if ($poll)
                <form method="POST" action="{{ route('admin.polls.publish', $poll) }}">
                    @csrf
                    <button type="submit" class="button">Publish</button>
                </form>
                <form method="POST" action="{{ route('admin.polls.close', $poll) }}">
                    @csrf
                    <button type="submit" class="button button-danger">Close</button>
                </form>
            @endif
        </div>
    </div>

    <div class="panel note-panel">
        <h2>Publishing Rules</h2>
        <ul class="compact-list">
            <li>Public poll reads are allowed through BFF with the Interactive service key.</li>
            <li>Voting is only available while the poll is live and before the close time.</li>
            <li>At least two active options are required before publishing.</li>
            <li>Use <code>live_percentages</code> if the app should show percentages before the poll closes.</li>
        </ul>
    </div>

    <form method="POST" action="{{ $poll ? route('admin.polls.update', $poll) : route('admin.polls.store') }}" class="stack-lg" enctype="multipart/form-data">
        @csrf
        @if ($poll)
            @method('PUT')
        @endif

        <div class="panel stack-lg">
            <div class="form-grid two-columns">
                <div>
                    <label for="title">Title <span class="required-mark">*</span></label>
                    <input type="text" name="title" id="title" value="{{ old('title', $form['title']) }}" required>
                </div>
                <div>
                    <label for="question">Question <span class="required-mark">*</span></label>
                    <input type="text" name="question" id="question" value="{{ old('question', $form['question']) }}" required>
                </div>
                <div>
                    <label for="slug">Slug</label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug', $form['slug']) }}">
                </div>
                <div>
                    <label for="category">Category</label>
                    <input type="text" name="category" id="category" value="{{ old('category', $form['category']) }}">
                </div>
                <div class="span-2">
                    <label for="short_description">Short Description</label>
                    <input type="text" name="short_description" id="short_description" value="{{ old('short_description', $form['short_description']) }}">
                </div>
                <div class="span-2">
                    <label for="description">Description</label>
                    <textarea name="description" id="description">{{ old('description', $form['description']) }}</textarea>
                </div>
            </div>
        </div>

        <div class="panel stack-lg">
            <div class="form-grid three-columns">
                <div>
                    <label for="visibility">Visibility <span class="required-mark">*</span></label>
                    <select name="visibility" id="visibility" required>
                        @foreach (['public', 'private'] as $visibility)
                            <option value="{{ $visibility }}" @selected(old('visibility', $form['visibility']) === $visibility)>{{ ucfirst($visibility) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="open_at">Open At</label>
                    <input type="datetime-local" name="open_at" id="open_at" value="{{ old('open_at', $form['open_at']) }}">
                </div>
                <div>
                    <label for="close_at">Close At</label>
                    <input type="datetime-local" name="close_at" id="close_at" value="{{ old('close_at', $form['close_at']) }}">
                </div>
                <div>
                    <label for="result_visibility_mode">Result Visibility <span class="required-mark">*</span></label>
                    <select name="result_visibility_mode" id="result_visibility_mode" required>
                        @foreach (['hidden_until_end', 'live_percentages', 'final_results'] as $mode)
                            <option value="{{ $mode }}" @selected(old('result_visibility_mode', $form['result_visibility_mode']) === $mode)>{{ ucfirst(str_replace('_', ' ', $mode)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sponsor_name">Sponsor Name</label>
                    <input type="text" name="sponsor_name" id="sponsor_name" value="{{ old('sponsor_name', $form['sponsor_name']) }}">
                </div>
                <div>
                    <label for="cover_image_upload">Cover Image Upload</label>
                    <input type="hidden" name="existing_cover_image_url" value="{{ old('existing_cover_image_url', $form['cover_image_url']) }}">
                    <input type="file" name="cover_image_upload" id="cover_image_upload" accept="image/*">
                    @if (old('existing_cover_image_url', $form['cover_image_url']))
                        <div class="media-meta">Current: <a href="{{ old('existing_cover_image_url', $form['cover_image_url']) }}" target="_blank">{{ old('existing_cover_image_url', $form['cover_image_url']) }}</a></div>
                    @endif
                </div>
                <div>
                    <label for="banner_image_upload">Banner Image Upload</label>
                    <input type="hidden" name="existing_banner_image_url" value="{{ old('existing_banner_image_url', $form['banner_image_url']) }}">
                    <input type="file" name="banner_image_upload" id="banner_image_upload" accept="image/*">
                    @if (old('existing_banner_image_url', $form['banner_image_url']))
                        <div class="media-meta">Current: <a href="{{ old('existing_banner_image_url', $form['banner_image_url']) }}" target="_blank">{{ old('existing_banner_image_url', $form['banner_image_url']) }}</a></div>
                    @endif
                </div>
                <div>
                    <label for="context_type">Context Type</label>
                    <input type="text" name="context_type" id="context_type" value="{{ old('context_type', $form['context_type']) }}">
                </div>
                <div>
                    <label for="context_id">Context ID</label>
                    <input type="text" name="context_id" id="context_id" value="{{ old('context_id', $form['context_id']) }}">
                </div>
            </div>

            <div class="form-grid three-columns">
                <div class="checkbox-row checkbox-tall">
                    <input type="checkbox" name="login_required" id="login_required" value="1" @checked(old('login_required', $form['login_required']))>
                    <label for="login_required">Require login to vote</label>
                </div>
                <div class="checkbox-row checkbox-tall">
                    <input type="checkbox" name="verified_account_required" id="verified_account_required" value="1" @checked(old('verified_account_required', $form['verified_account_required']))>
                    <label for="verified_account_required">Require verified account</label>
                </div>
                <div class="checkbox-row checkbox-tall">
                    <input type="checkbox" name="allow_result_view_before_vote" id="allow_result_view_before_vote" value="1" @checked(old('allow_result_view_before_vote', $form['allow_result_view_before_vote']))>
                    <label for="allow_result_view_before_vote">Allow results before vote</label>
                </div>
            </div>
        </div>

        <div class="panel stack-lg">
            <div class="panel-header">
                <div>
                    <h2>Options</h2>
                    <p class="muted">Fill any two or more option rows. Leave unused rows blank and they will be ignored.</p>
                </div>
            </div>

            @foreach ($form['options'] as $index => $option)
                <div class="question-card stack-md">
                    <div class="question-header">
                        <h2>Option {{ $index + 1 }}</h2>
                        <span>{{ old('options.'.$index.'.status', $option['status']) === 'active' ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <input type="hidden" name="options[{{ $index }}][id]" value="{{ old('options.'.$index.'.id', $option['id']) }}">
                    <input type="hidden" name="options[{{ $index }}][existing_image_url]" value="{{ old('options.'.$index.'.existing_image_url', $option['image_url']) }}">
                    <input type="hidden" name="options[{{ $index }}][existing_video_url]" value="{{ old('options.'.$index.'.existing_video_url', $option['video_url']) }}">
                    <input type="hidden" name="options[{{ $index }}][existing_thumbnail_url]" value="{{ old('options.'.$index.'.existing_thumbnail_url', $option['thumbnail_url']) }}">
                    <div class="form-grid two-columns">
                        <div>
                            <label for="options_{{ $index }}_title">Title</label>
                            <input type="text" id="options_{{ $index }}_title" name="options[{{ $index }}][title]" value="{{ old('options.'.$index.'.title', $option['title']) }}">
                        </div>
                        <div>
                            <label for="options_{{ $index }}_subtitle">Subtitle</label>
                            <input type="text" id="options_{{ $index }}_subtitle" name="options[{{ $index }}][subtitle]" value="{{ old('options.'.$index.'.subtitle', $option['subtitle']) }}">
                        </div>
                        <div class="span-2">
                            <label for="options_{{ $index }}_description">Description</label>
                            <textarea id="options_{{ $index }}_description" name="options[{{ $index }}][description]">{{ old('options.'.$index.'.description', $option['description']) }}</textarea>
                        </div>
                        <div>
                            <label for="options_{{ $index }}_image_upload">Image Upload</label>
                            <input type="file" id="options_{{ $index }}_image_upload" name="options[{{ $index }}][image_upload]" accept="image/*">
                            @if (old('options.'.$index.'.existing_image_url', $option['image_url']))
                                <div class="media-meta">Current: <a href="{{ old('options.'.$index.'.existing_image_url', $option['image_url']) }}" target="_blank">{{ old('options.'.$index.'.existing_image_url', $option['image_url']) }}</a></div>
                            @endif
                        </div>
                        <div>
                            <label for="options_{{ $index }}_video_upload">Video Upload</label>
                            <input type="file" id="options_{{ $index }}_video_upload" name="options[{{ $index }}][video_upload]" accept="video/mp4,video/webm,video/quicktime,video/x-msvideo">
                            @if (old('options.'.$index.'.existing_video_url', $option['video_url']))
                                <div class="media-meta">Current: <a href="{{ old('options.'.$index.'.existing_video_url', $option['video_url']) }}" target="_blank">{{ old('options.'.$index.'.existing_video_url', $option['video_url']) }}</a></div>
                            @endif
                        </div>
                        <div>
                            <label for="options_{{ $index }}_thumbnail_upload">Thumbnail Upload</label>
                            <input type="file" id="options_{{ $index }}_thumbnail_upload" name="options[{{ $index }}][thumbnail_upload]" accept="image/*">
                            @if (old('options.'.$index.'.existing_thumbnail_url', $option['thumbnail_url']))
                                <div class="media-meta">Current: <a href="{{ old('options.'.$index.'.existing_thumbnail_url', $option['thumbnail_url']) }}" target="_blank">{{ old('options.'.$index.'.existing_thumbnail_url', $option['thumbnail_url']) }}</a></div>
                            @endif
                        </div>
                        <div>
                            <label for="options_{{ $index }}_badge_text">Badge Text</label>
                            <input type="text" id="options_{{ $index }}_badge_text" name="options[{{ $index }}][badge_text]" value="{{ old('options.'.$index.'.badge_text', $option['badge_text']) }}">
                        </div>
                        <div>
                            <label for="options_{{ $index }}_stats_summary">Stats Summary</label>
                            <input type="text" id="options_{{ $index }}_stats_summary" name="options[{{ $index }}][stats_summary]" value="{{ old('options.'.$index.'.stats_summary', $option['stats_summary']) }}">
                        </div>
                        <div>
                            <label for="options_{{ $index }}_entity_type">Entity Type</label>
                            <input type="text" id="options_{{ $index }}_entity_type" name="options[{{ $index }}][entity_type]" value="{{ old('options.'.$index.'.entity_type', $option['entity_type']) }}">
                        </div>
                        <div>
                            <label for="options_{{ $index }}_entity_id">Entity ID</label>
                            <input type="text" id="options_{{ $index }}_entity_id" name="options[{{ $index }}][entity_id]" value="{{ old('options.'.$index.'.entity_id', $option['entity_id']) }}">
                        </div>
                        <div>
                            <label for="options_{{ $index }}_status">Status</label>
                            <select id="options_{{ $index }}_status" name="options[{{ $index }}][status]">
                                @foreach (['active', 'inactive'] as $status)
                                    <option value="{{ $status }}" @selected(old('options.'.$index.'.status', $option['status']) === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="button-row">
            <button type="submit" class="button">Save Poll</button>
        </div>
    </form>
@endsection
