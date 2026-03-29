<?php

namespace App\Http\Controllers\Api\Predictor;

use App\Auth\JwtUser;
use App\Data\AuthBoxUserProfile;
use App\Http\Controllers\Controller;
use App\Models\PredictorCampaign;
use App\Models\PredictorRound;
use App\Services\PredictorEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PredictorEntryController extends Controller
{
    public function __construct(private readonly PredictorEntryService $entries)
    {
    }

    public function myEntry(Request $request, PredictorRound $round): JsonResponse
    {
        /** @var JwtUser $user */
        $user = $request->user();

        return response()->json([
            'entry' => $this->entries->myEntry($round->loadMissing('fixtures'), $user->userId()),
        ]);
    }

    public function draft(Request $request, PredictorRound $round): JsonResponse
    {
        /** @var JwtUser $user */
        $user = $request->user();
        /** @var AuthBoxUserProfile|null $profile */
        $profile = $request->attributes->get('current_user_profile');

        $validated = $request->validate([
            'predictions' => ['required', 'array', 'min:1'],
            'predictions.*.round_fixture_id' => ['required', 'integer'],
            'predictions.*.predicted_home_score' => ['required', 'integer', 'min:0'],
            'predictions.*.predicted_away_score' => ['required', 'integer', 'min:0'],
            'predictions.*.is_banker' => ['nullable', 'boolean'],
        ]);

        $campaign = PredictorCampaign::findOrFail($round->season->campaign_id);

        return response()->json($this->entries->saveDraft($round, $campaign, $user->userId(), $profile, $validated['predictions']));
    }

    public function submit(Request $request, PredictorRound $round): JsonResponse
    {
        /** @var JwtUser $user */
        $user = $request->user();
        /** @var AuthBoxUserProfile|null $profile */
        $profile = $request->attributes->get('current_user_profile');

        $validated = $request->validate([
            'predictions' => ['required', 'array', 'min:1'],
            'predictions.*.round_fixture_id' => ['required', 'integer'],
            'predictions.*.predicted_home_score' => ['required', 'integer', 'min:0'],
            'predictions.*.predicted_away_score' => ['required', 'integer', 'min:0'],
            'predictions.*.is_banker' => ['nullable', 'boolean'],
        ]);

        $campaign = PredictorCampaign::findOrFail($round->season->campaign_id);

        return response()->json($this->entries->submit($round, $campaign, $user->userId(), $profile, $validated['predictions']));
    }
}
