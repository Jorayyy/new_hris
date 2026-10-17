<?php

namespace App\Services;

/**
 * Reads a biometric device export into per-day in/out pairs.
 *
 * Handles both shapes devices export:
 *  - paired rows: employee_no, date, time_in, time_out
 *  - one row per punch: user id, name, date, time, state
 */
class BiometricImportService
{
    /** Cap on reported problems, so a junk file cannot blow up the session. */
    public const MAX_REPORTED = 25;

    /**
     * @return array{rows: list<array{employee_no: string, date: string, time_in: string, time_out: ?string, line: int}>, problems: list<array{line: int, raw: string, reason: string}>}
     */
    public function parse(string $path): array
    {
        $days = [];
        $problems = [];
        $line = 0;

        if (($handle = fopen($path, 'r')) === false) {
            return ['rows' => [], 'problems' => [['line' => 0, 'raw' => '', 'reason' => 'File could not be read']]];
        }

        while (($fields = fgetcsv($handle)) !== false) {
            $line++;
            $values = array_values(array_filter(
                array_map(fn ($field) => trim((string) $field), $fields),
                fn ($value) => $value !== ''
            ));

            if ($values === []) {
                continue;
            }

            $dates = [];
            $times = [];
            foreach (array_slice($values, 1) as $value) {
                $this->classify($value, $dates, $times);
            }

            if ($dates === [] && $times === []) {
                continue; // header row, trailer, or anything else with no date/time in it
            }

            $raw = implode(', ', $values);

            if ($dates === []) {
                $this->problem($problems, $line, $raw, 'No recognizable date');

                continue;
            }

            if ($times === []) {
                $this->problem($problems, $line, $raw, 'No recognizable time');

                continue;
            }

            $employeeNo = $values[0];
            $date = $dates[0];
            $key = $employeeNo.'|'.$date;
            $in = $times[0];
            // Two or more times on one row means the device paired them: first is in, last is out.
            // A lone time is both an in and an out candidate; the day keeps the earliest and latest.
            $out = count($times) > 1 ? $times[count($times) - 1] : $times[0];

            $day = $days[$key] ?? ['employee_no' => $employeeNo, 'date' => $date, 'time_in' => null, 'time_out' => null, 'line' => $line];
            if ($day['time_in'] === null || $in < $day['time_in']) {
                $day['time_in'] = $in;
            }
            if ($out !== null && ($day['time_out'] === null || $out > $day['time_out'])) {
                $day['time_out'] = $out;
            }
            $days[$key] = $day;
        }

        fclose($handle);

        // A single punch is an in with no out yet, not a zero-hour day.
        foreach ($days as $key => $day) {
            if ($day['time_out'] === $day['time_in']) {
                $days[$key]['time_out'] = null;
            }
        }

        return ['rows' => array_values($days), 'problems' => $problems];
    }

    /** @param list<string> $dates @param list<string> $times */
    private function classify(string $value, array &$dates, array &$times): void
    {
        if (str_contains($value, ' ')) {
            [$head, $tail] = explode(' ', $value, 2);
            if (($date = $this->date($head)) !== null) {
                $dates[] = $date;
                if (($time = $this->time($tail)) !== null) {
                    $times[] = $time;
                }

                return;
            }
        }

        if (($date = $this->date($value)) !== null) {
            $dates[] = $date;

            return;
        }

        if (($time = $this->time($value)) !== null) {
            $times[] = $time;
        }
    }

    private function date(string $value): ?string
    {
        if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $value, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1])
                ? sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3])
                : null;
        }

        // Ambiguous d/m/Y: day first (PH convention), month first only if day-first is impossible.
        if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})$/', $value, $m)) {
            $first = (int) $m[1];
            $second = (int) $m[2];
            $year = (int) $m[3];

            if (checkdate($second, $first, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $second, $first);
            }

            if (checkdate($first, $second, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $first, $second);
            }
        }

        return null;
    }

    private function time(string $value): ?string
    {
        $value = strtoupper(trim($value));

        if (preg_match('/^(\d{1,2})[.:](\d{2})(?:[.:]\d{2})?\s*([AP])\.?M\.?$/', $value, $m)) {
            $hour = ((int) $m[1] % 12) + ($m[3] === 'P' ? 12 : 0);

            return sprintf('%02d:%02d', $hour, (int) $m[2]);
        }

        if (preg_match('/^(\d{1,2})[.:](\d{2})(?:[.:]\d{2})?$/', $value, $m)) {
            $hour = (int) $m[1];

            return $hour < 24 && (int) $m[2] < 60 ? sprintf('%02d:%02d', $hour, (int) $m[2]) : null;
        }

        return null;
    }

    /** @param list<array{line: int, raw: string, reason: string}> $problems */
    private function problem(array &$problems, int $line, string $raw, string $reason): void
    {
        if (count($problems) < self::MAX_REPORTED) {
            $problems[] = ['line' => $line, 'raw' => $raw, 'reason' => $reason];
        } elseif (count($problems) === self::MAX_REPORTED) {
            $problems[] = ['line' => 0, 'raw' => '', 'reason' => 'Further problems not listed'];
        }
    }
}
