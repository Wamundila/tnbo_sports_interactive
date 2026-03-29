<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredictorCampaignCompetition extends Model
{
    protected $fillable = [
        'campaign_id',
        'competition_id',
        'competition_name_snapshot',
        'sort_order',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PredictorCampaign::class, 'campaign_id');
    }
}
