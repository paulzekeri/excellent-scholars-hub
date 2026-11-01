<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Free wording assistant for learning goals.
 *
 * "Can a free ai agent be employed to correct the goals text / wordings?" — the
 * public LanguageTool API (https://languagetool.org) is free, needs no account
 * or API key, and returns grammar/spelling/style corrections. We apply the first
 * suggested replacement for every match and hand the corrected paragraph back so
 * the tutor can review it before saving.
 *
 * The school can point LANGUAGETOOL_URL at a self-hosted LanguageTool instance,
 * or set GOAL_WORDING_ASSISTANT=false to switch the button off entirely.
 */
class GoalWordingService
{
    /**
     * @return array{ok: bool, corrected: string, suggestions: array<int, array<string, string>>, issues: int, message: string}
     */
    public function assist(string $text): array
    {
        $original = trim($text);

        if ($original === '') {
            return $this->failure($original, 'Write the goal first, then ask for wording help.');
        }

        if (! config('services.languagetool.enabled', true)) {
            return $this->failure($original, 'The wording assistant is switched off on this installation.');
        }

        $url = (string) config('services.languagetool.url', 'https://api.languagetool.org/v2/check');

        try {
            $response = Http::timeout((int) config('services.languagetool.timeout', 15))
                ->acceptJson()
                ->asForm()
                ->post($url, [
                    'text' => $original,
                    'language' => (string) config('services.languagetool.language', 'en-US'),
                    'enabledOnly' => 'false',
                ]);
        } catch (Throwable $exception) {
            return $this->failure($original, 'The wording assistant could not be reached. Check the internet connection and try again.');
        }

        if ($response->failed()) {
            return $this->failure($original, 'The wording assistant is unavailable right now. Please try again in a moment.');
        }

        return $this->applyMatches($original, (array) $response->json('matches', []));
    }

    /**
     * @param  array<int, array<string, mixed>>  $matches
     * @return array{ok: bool, corrected: string, suggestions: array<int, array<string, string>>, issues: int, message: string}
     */
    private function applyMatches(string $original, array $matches): array
    {
        // LanguageTool returns matches in reading order; sort defensively.
        usort($matches, function ($a, $b) {
            return ((int) ($a['offset'] ?? 0)) <=> ((int) ($b['offset'] ?? 0));
        });

        $corrected = '';
        $cursor = 0;
        $suggestions = [];

        foreach ($matches as $match) {
            $offset = (int) ($match['offset'] ?? 0);
            $length = (int) ($match['length'] ?? 0);
            $replacement = $match['replacements'][0]['value'] ?? null;

            // Skip overlapping matches and rules without a suggested replacement.
            if ($replacement === null || $offset < $cursor || $offset > strlen($original)) {
                continue;
            }

            $originalText = substr($original, $offset, $length);

            $corrected .= substr($original, $cursor, $offset - $cursor) . $replacement;
            $cursor = $offset + $length;

            $suggestions[] = [
                'before' => $originalText,
                'after' => (string) $replacement,
                'message' => (string) ($match['message'] ?? 'Suggested change'),
            ];
        }

        $corrected .= substr($original, $cursor);

        $issues = count($suggestions);

        return [
            'ok' => true,
            'corrected' => trim($corrected),
            'suggestions' => $suggestions,
            'issues' => $issues,
            'message' => $issues === 0
                ? 'No wording problems found — the goal reads well.'
                : ($issues === 1
                    ? '1 wording suggestion applied. Review it before saving.'
                    : $issues . ' wording suggestions applied. Review them before saving.'),
        ];
    }

    /**
     * @return array{ok: bool, corrected: string, suggestions: array<int, array<string, string>>, issues: int, message: string}
     */
    private function failure(string $original, string $message): array
    {
        return [
            'ok' => false,
            'corrected' => $original,
            'suggestions' => [],
            'issues' => 0,
            'message' => $message,
        ];
    }
}
