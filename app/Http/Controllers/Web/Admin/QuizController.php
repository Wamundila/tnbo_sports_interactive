<?php

namespace App\Http\Controllers\Web\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DuplicateTriviaQuizRequest;
use App\Http\Requests\Admin\UpsertTriviaQuizRequest;
use App\Models\TriviaQuiz;
use App\Services\AdminTriviaQuizService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['draft', 'scheduled', 'published', 'closed', 'archived'])],
        ]);

        $query = TriviaQuiz::query()
            ->withCount(['questions', 'attempts'])
            ->withAvg('attempts', 'score_total')
            ->orderByDesc('quiz_date');

        if (($filters['status'] ?? '') !== '') {
            $query->where('status', $filters['status']);
        }

        return view('admin.quizzes.index', [
            'filters' => [
                'status' => $filters['status'] ?? '',
            ],
            'quizzes' => $query->paginate(12)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.quizzes.form', [
            'pageTitle' => 'Create Quiz',
            'quiz' => null,
            'form' => $this->formData(),
        ]);
    }

    public function store(UpsertTriviaQuizRequest $request, AdminTriviaQuizService $quizService): RedirectResponse
    {
        try {
            $quiz = $quizService->create($this->normalizedPayload($request), Auth::guard('admin')->user());
        } catch (ApiException $exception) {
            return back()->withInput()->withErrors(['quiz' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.quizzes.edit', $quiz)
            ->with('status', 'Quiz created successfully.');
    }

    public function edit(TriviaQuiz $quiz): View
    {
        $quiz->loadMissing('questions.options');

        return view('admin.quizzes.form', [
            'pageTitle' => 'Edit Quiz',
            'quiz' => $quiz,
            'form' => $this->formData($quiz),
        ]);
    }

    public function update(UpsertTriviaQuizRequest $request, TriviaQuiz $quiz, AdminTriviaQuizService $quizService): RedirectResponse
    {
        try {
            $quizService->update($quiz, $this->normalizedPayload($request), Auth::guard('admin')->user());
        } catch (ApiException $exception) {
            return back()->withInput()->withErrors(['quiz' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.quizzes.edit', $quiz)
            ->with('status', 'Quiz updated successfully.');
    }

    public function publish(TriviaQuiz $quiz, AdminTriviaQuizService $quizService): RedirectResponse
    {
        try {
            $quizService->publish($quiz, Auth::guard('admin')->user());
        } catch (ApiException $exception) {
            return back()->withErrors(['quiz' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.quizzes.edit', $quiz)
            ->with('status', 'Quiz published successfully.');
    }

    public function close(TriviaQuiz $quiz, AdminTriviaQuizService $quizService): RedirectResponse
    {
        $quizService->close($quiz, Auth::guard('admin')->user());

        return redirect()
            ->route('admin.quizzes.edit', $quiz)
            ->with('status', 'Quiz closed successfully.');
    }

    public function duplicate(DuplicateTriviaQuizRequest $request, TriviaQuiz $quiz, AdminTriviaQuizService $quizService): RedirectResponse
    {
        try {
            $newQuiz = $quizService->duplicate($quiz, $request->validated(), Auth::guard('admin')->user());
        } catch (ApiException $exception) {
            return back()->withInput()->withErrors(['duplicate' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.quizzes.edit', $newQuiz)
            ->with('status', 'Quiz duplicated successfully.');
    }

    private function formData(?TriviaQuiz $quiz = null): array
    {
        $quiz?->loadMissing('questions.options');

        $questionTotal = max(
            3,
            $quiz?->questions->count() ?? 0,
            (int) ($quiz?->question_count_expected ?? 0)
        );

        $questions = [];

        for ($questionIndex = 0; $questionIndex < $questionTotal; $questionIndex++) {
            $question = $quiz?->questions->get($questionIndex);
            $optionTotal = max(3, $question?->options->count() ?? 0);
            $options = [];

            for ($optionIndex = 0; $optionIndex < $optionTotal; $optionIndex++) {
                $option = $question?->options->get($optionIndex);
                $options[] = [
                    'id' => $option?->id,
                    'position' => $optionIndex + 1,
                    'option_text' => $option?->option_text ?? '',
                    'is_correct' => (bool) ($option?->is_correct ?? ($optionIndex === 0)),
                ];
            }

            $questions[] = [
                'id' => $question?->id,
                'position' => $questionIndex + 1,
                'question_text' => $question?->question_text ?? '',
                'image_url' => $question?->image_url ?? '',
                'explanation_text' => $question?->explanation_text ?? '',
                'source_type' => $question?->source_type ?? '',
                'source_ref' => $question?->source_ref ?? '',
                'difficulty' => $question?->difficulty ?? 'medium',
                'sport_slug' => $question?->sport_slug ?? 'general',
                'status' => $question?->status ?? 'active',
                'options' => $options,
            ];
        }

        return [
            'quiz_date' => $quiz?->quiz_date?->toDateString() ?? now()->toDateString(),
            'title' => $quiz?->title ?? "Today's TNBO Sports Trivia",
            'short_description' => $quiz?->short_description ?? 'Three questions. Verified users only.',
            'trivia_banner_url' => $quiz?->trivia_banner_url ?? '',
            'status' => $quiz?->status ?? 'draft',
            'opens_at' => $quiz?->opens_at?->format('Y-m-d\\TH:i') ?? now()->addHour()->format('Y-m-d\\TH:i'),
            'closes_at' => $quiz?->closes_at?->format('Y-m-d\\TH:i') ?? now()->addHours(10)->format('Y-m-d\\TH:i'),
            'question_count_expected' => $quiz?->question_count_expected ?? $questionTotal,
            'time_per_question_seconds' => $quiz?->time_per_question_seconds ?? 30,
            'points_per_correct' => $quiz?->points_per_correct ?? 3,
            'streak_bonus_enabled' => $quiz?->streak_bonus_enabled ?? true,
            'sport_slug' => $quiz?->sport_slug ?? 'general',
            'questions' => $questions,
        ];
    }

    private function normalizedPayload(UpsertTriviaQuizRequest $request): array
    {
        $validated = $request->validated();
        $triviaBannerUrl = $this->storeMediaUpload(
            $request->file('trivia_banner_upload'),
            $validated['existing_trivia_banner_url'] ?? null,
            'banners'
        );
        $questions = collect($validated['questions'] ?? [])
            ->values()
            ->map(function (array $question, int $questionIndex): array {
                $options = collect($question['options'] ?? [])
                    ->values()
                    ->map(function (array $option, int $optionIndex): array {
                        return [
                            'id' => $option['id'] ?? null,
                            'position' => $optionIndex + 1,
                            'option_text' => $option['option_text'],
                            'is_correct' => (bool) ($option['is_correct'] ?? false),
                        ];
                    })
                    ->all();

                return [
                    'id' => $question['id'] ?? null,
                    'position' => $questionIndex + 1,
                    'question_text' => $question['question_text'],
                    'image_url' => $question['image_url'] ?? null,
                    'explanation_text' => $question['explanation_text'] ?? null,
                    'source_type' => $question['source_type'] ?? null,
                    'source_ref' => $question['source_ref'] ?? null,
                    'difficulty' => $question['difficulty'] ?? 'medium',
                    'sport_slug' => $question['sport_slug'] ?? ($validated['sport_slug'] ?? 'general'),
                    'status' => $question['status'] ?? 'active',
                    'options' => $options,
                ];
            })
            ->all();

        return [
            'quiz_date' => $validated['quiz_date'],
            'title' => $validated['title'],
            'short_description' => $validated['short_description'] ?? null,
            'trivia_banner_url' => $triviaBannerUrl,
            'status' => $validated['status'] ?? 'draft',
            'opens_at' => $validated['opens_at'] ?? null,
            'closes_at' => $validated['closes_at'] ?? null,
            'question_count_expected' => $validated['question_count_expected'] ?? count($questions),
            'time_per_question_seconds' => $validated['time_per_question_seconds'] ?? 30,
            'points_per_correct' => $validated['points_per_correct'] ?? 3,
            'streak_bonus_enabled' => $request->boolean('streak_bonus_enabled'),
            'sport_slug' => $validated['sport_slug'] ?? 'general',
            'metadata' => $validated['metadata'] ?? [],
            'questions' => $questions,
        ];
    }

    private function storeMediaUpload(?UploadedFile $file, ?string $existingPath, string $segment): ?string
    {
        if (! $file instanceof UploadedFile) {
            return is_string($existingPath) && trim($existingPath) !== '' ? $existingPath : null;
        }

        $directory = public_path('uploads/trivia/'.$segment);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
        $filename = now()->format('YmdHis').'-'.Str::lower(Str::random(16)).'.'.$extension;
        $file->move($directory, $filename);

        return '/uploads/trivia/'.$segment.'/'.$filename;
    }
}
