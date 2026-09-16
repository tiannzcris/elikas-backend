<?php

namespace App\Http\Resources;

use App\Models\EvacuationCenterQuickCount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvacuationCenterQuickCountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sectoralGroups = $this->whenLoaded('sectoralGroups', fn () => $this->sectoralGroups->keyBy('sectoral_group'), collect());

        $sectoralGroupRows = collect(EvacuationCenterQuickCount::SECTORAL_GROUPS)->map(fn ($group) => [
            'sectoral_group' => $group,
            'male_count' => (int) ($sectoralGroups[$group]->male_count ?? 0),
            'female_count' => (int) ($sectoralGroups[$group]->female_count ?? 0),
        ]);

        // Live, not stored -- see EvacuationCenterQuickCount::liveAgeSexBreakdown()
        // and its sibling methods. Computed from actual Evacuee/EvacuationRecord
        // rows every time the board is viewed, so it can never drift out of
        // sync with who's actually registered. Includes a trailing
        // 'unclassified' row (see that method's docblock) for anyone
        // missing sex/age_bracket entirely -- age_groups_total below is
        // built to still equal persons_now exactly even then, rather than
        // quietly under-counting whoever that row represents.
        $ageGroupRows = collect($this->resource->liveAgeSexBreakdown());
        $classifiedRows = $ageGroupRows->where('age_bracket', '!=', 'unclassified');
        $unclassifiedRow = $ageGroupRows->firstWhere('age_bracket', 'unclassified');

        return [
            'id' => $this->id,
            'evacuation_center_id' => $this->evacuation_center_id,
            'evacuation_event_id' => $this->evacuation_event_id,
            'families_cumulative' => $this->families_cumulative,
            'families_now' => $this->resource->liveFamiliesNow(),
            'persons_cumulative' => $this->persons_cumulative,
            'persons_now' => $this->resource->livePersonsNow(),
            'beneficiaries_4ps' => $this->beneficiaries_4ps,
            'age_groups' => $ageGroupRows->values(),
            // Derived, not stored -- age brackets are mutually exclusive so
            // this sum is a meaningful "Total" row for that table. Sectoral
            // categories overlap (a solo parent can also be a PWD), so no
            // equivalent grand total is computed for that one. Uses the
            // unclassified row's total_count (not male_count + female_count,
            // which excludes anyone whose sex is ALSO unknown) so this
            // always equals persons_now above -- if it doesn't, that's a
            // real bug to investigate, not something to paper over here.
            'age_groups_total' => [
                'male_count' => $classifiedRows->sum('male_count') + ($unclassifiedRow['male_count'] ?? 0),
                'female_count' => $classifiedRows->sum('female_count') + ($unclassifiedRow['female_count'] ?? 0),
                'total_persons' => $classifiedRows->sum('male_count') + $classifiedRows->sum('female_count')
                    + ($unclassifiedRow['total_count'] ?? 0),
            ],
            'sectoral_groups' => $sectoralGroupRows,
            'updated_by' => $this->updated_by,
            'updated_by_name' => $this->whenLoaded('updater', fn () => $this->updater?->name),
            'updated_at' => $this->updated_at,
        ];
    }
}
