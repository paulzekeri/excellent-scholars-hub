<?php

namespace App\Services;

use App\Models\ClassSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Creates one class session - or a whole weekly series - in a single step.
 *
 * "Class schedule should be set once with a repeat ability (ie set for Monday
 * with repeat so the schedule appear every Monday same time with the users and
 * video link automatic)." Each occurrence is an ordinary class_schedules row,
 * so the learner/tutor dashboards, attendance and the tutor report work exactly
 * as before; the rows of one series share a recurrence_group.
 *
 * When the platform is "Automatic video room" (Jitsi Meet) and no link is typed,
 * a working room link is generated once and reused for every occurrence, so the
 * tutor and learners always join the same room.
 */
class ClassScheduleService
{
    public const PLATFORM_GOOGLE_MEET = 'google_meet';
    public const PLATFORM_ZOOM = 'zoom';
    public const PLATFORM_JITSI = 'jitsi';

    public const REPEAT_NONE = 'none';
    public const REPEAT_WEEKLY = 'weekly';

    /** Never generate more than a year of weekly sessions in one go. */
    private const MAX_OCCURRENCES = 52;

    /**
     * Platforms offered on the schedule forms.
     *
     * @return array<string, string>
     */
    public static function platforms(): array
    {
        return [
            self::PLATFORM_JITSI => 'Automatic video room (Jitsi Meet)',
            self::PLATFORM_GOOGLE_MEET => 'Google Meet',
            self::PLATFORM_ZOOM => 'Zoom',
        ];
    }

    /**
     * Create the session(s) and return the rows that were created.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, ClassSchedule>
     */
    public function createSeries(array $data): Collection
    {
        $start = Carbon::parse($data['start_time']);
        $end = Carbon::parse($data['end_time']);
        $duration = max(1, $start->diffInMinutes($end));

        $repeat = $data['repeat_rule'] ?? $data['repeat'] ?? self::REPEAT_NONE;
        $repeat = $repeat === self::REPEAT_WEEKLY ? self::REPEAT_WEEKLY : self::REPEAT_NONE;

        $until = ! empty($data['repeat_until'])
            ? Carbon::parse($data['repeat_until'])->endOfDay()
            : null;

        $occurrences = [$start->copy()];

        if ($repeat === self::REPEAT_WEEKLY && $until) {
            $cursor = $start->copy();

            while (count($occurrences) < self::MAX_OCCURRENCES) {
                $cursor = $cursor->copy()->addWeek();

                if ($cursor->greaterThan($until)) {
                    break;
                }

                $occurrences[] = $cursor->copy();
            }
        }

        $group = count($occurrences) > 1 ? (string) Str::uuid() : null;

        $platform = $data['meeting_platform'] ?? null;
        $link = trim((string) ($data['meeting_link'] ?? ''));

        if ($platform === self::PLATFORM_JITSI && $link === '') {
            $link = $this->automaticRoom();
        }

        return collect($occurrences)->map(function (Carbon $occurrence) use ($data, $duration, $group, $until, $platform, $link) {
            return ClassSchedule::create([
                'class_id' => $data['class_id'],
                'tutor_id' => $data['tutor_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'start_time' => $occurrence,
                'end_time' => $occurrence->copy()->addMinutes($duration),
                'meeting_link' => $link !== '' ? $link : null,
                'meeting_platform' => $platform ?: null,
                'status' => $data['status'] ?? 'scheduled',
                'recurrence_rule' => $group ? self::REPEAT_WEEKLY : self::REPEAT_NONE,
                'recurrence_until' => $group && $until ? $until->toDateString() : null,
                'recurrence_group' => $group,
            ]);
        });
    }

    /**
     * A free, instant video room that needs no school account or API key.
     * Everyone in the series joins the same URL.
     */
    public function automaticRoom(): string
    {
        return 'https://meet.jit.si/esh-' . Str::lower(Str::random(12));
    }
}
