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
 * A weekly series may run on several days at different times - 10:00 Monday,
 * 14:00 Wednesday and 18:00 Friday, for example. Each day/time pair is a
 * "slot": the slot repeats on its own weekday at its own time, every week,
 * from the series start date until the repeat end date.
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

    /** Never generate more than a year of sessions for one day/time slot. */
    private const MAX_OCCURRENCES_PER_SLOT = 52;

    /** Hard ceiling for one submission - a year of all seven days is 364. */
    private const MAX_OCCURRENCES = 364;

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
     * Day choices for the weekly "days & times" builder, keyed by ISO-8601
     * weekday number (1 = Monday ... 7 = Sunday).
     *
     * @return array<int, string>
     */
    public static function weekdays(): array
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];
    }

    /**
     * The Monday that starts the week containing $value. A blank, hand-typed or
     * broken value falls back to the current week, so a bad `?week=` link can
     * never break the admin or tutor schedules screens.
     */
    public static function weekStart($value = null): Carbon
    {
        $date = null;

        if (is_string($value) && trim($value) !== '') {
            try {
                $date = Carbon::parse(trim($value));
            } catch (\Throwable $e) {
                $date = null;
            }
        }

        return ($date ?? now())->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    /**
     * Validation rules that depend on the repeat choice. Shared by the admin and
     * tutor schedule forms so both behave the same way.
     *
     * @return array<string, string>
     */
    public static function repeatRules(string $repeat): array
    {
        if ($repeat === self::REPEAT_WEEKLY) {
            return [
                // The old single "Start Time" anchor is still accepted, so an
                // existing form (or an older bookmark) keeps working without slots.
                'start_time' => 'nullable|date',
                'end_time' => 'nullable|date',
                'repeats_from' => 'nullable|date',
                'repeat_until' => 'required|date',
                'slots' => 'nullable|array',
                'slots.*.day' => 'required_with:slots|integer|between:1,7',
                'slots.*.start' => 'required_with:slots|date_format:H:i',
                'slots.*.end' => 'required_with:slots|date_format:H:i',
            ];
        }

        return [
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'repeat_until' => 'nullable|date',
        ];
    }

    /**
     * Cross-field checks a plain rule cannot express once there are several
     * day/time slots: at least one slot must be given, and each slot has to end
     * after it starts.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, array<int, string>>
     */
    public function seriesErrors(array $data): array
    {
        if ($this->repeatMode($data) !== self::REPEAT_WEEKLY) {
            return [];
        }

        $slots = $this->weeklySlots($data);

        if ($slots === []) {
            return ['slots' => ['Add at least one day and time for the weekly repeat.']];
        }

        $errors = [];
        $from = $this->seriesStart($data);

        if ($from && ! empty($data['repeat_until'])) {
            $until = Carbon::parse($data['repeat_until'])->startOfDay();

            if ($until->lessThan($from)) {
                $errors['repeat_until'][] = 'The repeat end date must be on or after the date the series starts.';
            } else {
                $firstLesson = null;

                foreach ($slots as $slot) {
                    $first = $this->firstOccurrence($from, $slot['day'], $slot['start']);

                    if ($firstLesson === null || $first->lessThan($firstLesson)) {
                        $firstLesson = $first;
                    }
                }

                if ($firstLesson && $firstLesson->startOfDay()->greaterThan($until)) {
                    $errors['repeat_until'][] = 'The repeat end date is before the first lesson of the days you chose — pick a later date.';
                }
            }
        }

        foreach ($slots as $slot) {
            if ($slot['end']->lessThanOrEqualTo($slot['start'])) {
                $errors['slots'][] = self::weekdays()[$slot['day']] . ': the end time must be after the start time.';
            }
        }

        return $errors;
    }

    /**
     * Create the session(s) and return the rows that were created, earliest first.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, ClassSchedule>
     */
    public function createSeries(array $data): Collection
    {
        $repeat = $this->repeatMode($data);
        $occurrences = $this->occurrences($data, $repeat);

        $group = count($occurrences) > 1 ? (string) Str::uuid() : null;

        $platform = $data['meeting_platform'] ?? null;
        $link = trim((string) ($data['meeting_link'] ?? ''));

        if ($platform === self::PLATFORM_JITSI && $link === '') {
            $link = $this->automaticRoom();
        }

        $until = $repeat === self::REPEAT_WEEKLY && ! empty($data['repeat_until'])
            ? Carbon::parse($data['repeat_until'])->toDateString()
            : null;

        return collect($occurrences)
            ->map(function (array $occurrence) use ($data, $group, $until, $platform, $link) {
                $start = $occurrence['start'];

                return ClassSchedule::create([
                    'class_id' => $data['class_id'],
                    'tutor_id' => $data['tutor_id'],
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'start_time' => $start,
                    'end_time' => $start->copy()->addMinutes($occurrence['duration']),
                    'meeting_link' => $link !== '' ? $link : null,
                    'meeting_platform' => $platform ?: null,
                    'status' => $data['status'] ?? 'scheduled',
                    'recurrence_rule' => $group ? self::REPEAT_WEEKLY : self::REPEAT_NONE,
                    'recurrence_until' => $group ? $until : null,
                    'recurrence_group' => $group,
                ]);
            })
            ->sortBy(function (ClassSchedule $session) {
                return $session->start_time->timestamp;
            })
            ->values();
    }

    /**
     * A short list of the days and times in a series, used in the success
     * message: "Mon 10:00 am, Wed 2:00 pm, Fri 6:00 pm".
     *
     * @param  Collection<int, ClassSchedule>  $sessions
     */
    public static function slotSummary(Collection $sessions): string
    {
        return $sessions
            ->map(function (ClassSchedule $session) {
                return $session->start_time->format('D g:i a');
            })
            ->unique()
            ->values()
            ->implode(', ');
    }

    /**
     * Every session to create, earliest first.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array{start: Carbon, duration: int}>
     */
    private function occurrences(array $data, string $repeat): array
    {
        if ($repeat !== self::REPEAT_WEEKLY) {
            $start = Carbon::parse($data['start_time']);
            $end = Carbon::parse($data['end_time']);

            return [['start' => $start, 'duration' => max(1, $start->diffInMinutes($end))]];
        }

        $from = $this->seriesStart($data);

        if (! $from) {
            return [];
        }

        $until = Carbon::parse($data['repeat_until'])->endOfDay();
        $occurrences = [];

        foreach ($this->weeklySlots($data) as $slot) {
            $cursor = $this->firstOccurrence($from, $slot['day'], $slot['start']);

            for ($week = 0; $week < self::MAX_OCCURRENCES_PER_SLOT; $week++) {
                if ($cursor->greaterThan($until) || count($occurrences) >= self::MAX_OCCURRENCES) {
                    break;
                }

                $occurrences[] = ['start' => $cursor->copy(), 'duration' => $slot['duration']];
                $cursor->addWeek();
            }
        }

        usort($occurrences, function (array $a, array $b) {
            return $a['start'] <=> $b['start'];
        });

        return $occurrences;
    }

    /**
     * The posted day/time rows, normalised into slots. Falls back to the single
     * date typed in "Start Time" so the original one-day repeat still works.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array{day: int, start: Carbon, end: Carbon, duration: int}>
     */
    private function weeklySlots(array $data): array
    {
        $slots = [];

        foreach ((array) ($data['slots'] ?? []) as $row) {
            $day = (int) ($row['day'] ?? 0);
            $start = $this->timeOfDay($row['start'] ?? null);
            $end = $this->timeOfDay($row['end'] ?? null);

            if ($day < 1 || $day > 7 || ! $start || ! $end) {
                continue;
            }

            $slots[] = [
                'day' => $day,
                'start' => $start,
                'end' => $end,
                'duration' => max(1, $start->diffInMinutes($end)),
            ];
        }

        if ($slots !== []) {
            return $slots;
        }

        if (! empty($data['start_time'])) {
            $start = Carbon::parse($data['start_time']);
            $end = ! empty($data['end_time'])
                ? Carbon::parse($data['end_time'])
                : $start->copy()->addHour();

            $slots[] = [
                'day' => $start->dayOfWeekIso,
                'start' => $start->copy(),
                'end' => $end->copy(),
                'duration' => max(1, $start->diffInMinutes($end)),
            ];
        }

        return $slots;
    }

    /** The date the weekly series starts (defaults to the legacy Start Time). */
    private function seriesStart(array $data): ?Carbon
    {
        $from = $data['repeats_from'] ?? null;

        if (empty($from)) {
            $from = $data['start_time'] ?? null;
        }

        return empty($from) ? null : Carbon::parse($from)->startOfDay();
    }

    /** The first time this weekday/time slot falls on or after the start date. */
    private function firstOccurrence(Carbon $from, int $isoDay, Carbon $time): Carbon
    {
        $date = $from->copy()->startOfDay();
        $offset = ($isoDay - $date->dayOfWeekIso + 7) % 7;

        return $date->addDays($offset)->setTime($time->hour, $time->minute, 0);
    }

    /** A time-of-day string ("14:00" or "14:00:00") as a Carbon instance. */
    private function timeOfDay($time): ?Carbon
    {
        $time = trim((string) $time);

        if ($time === '') {
            return null;
        }

        try {
            return Carbon::parse('1970-01-01 ' . $time);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** @param  array<string, mixed>  $data */
    private function repeatMode(array $data): string
    {
        $repeat = $data['repeat_rule'] ?? $data['repeat'] ?? self::REPEAT_NONE;

        return $repeat === self::REPEAT_WEEKLY ? self::REPEAT_WEEKLY : self::REPEAT_NONE;
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
