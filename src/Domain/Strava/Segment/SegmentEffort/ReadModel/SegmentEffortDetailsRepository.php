<?php

declare(strict_types=1);

namespace App\Domain\Strava\Segment\SegmentEffort\ReadModel;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffort;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEffortId;
use App\Domain\Strava\Segment\SegmentEffort\SegmentEfforts;
use App\Domain\Strava\Segment\SegmentId;

interface SegmentEffortDetailsRepository
{
    public function find(SegmentEffortId $segmentEffortId): SegmentEffort;

    public function findBySegmentId(SegmentId $segmentId, ?int $limit = null): SegmentEfforts;

    public function countBySegmentId(SegmentId $segmentId): int;

    public function findByActivityId(ActivityId $activityId): SegmentEfforts;
}
