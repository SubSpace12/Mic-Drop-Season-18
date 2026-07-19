<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function index()
    {
        return view('search');
    }

    public function query(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:200',
        ]);

        $query = trim((string) $request->input('q', ''));
        if ($query === '') {
            return response()->json(['results' => []]);
        }

        $activeSeason = DB::table('season')->where('active', true)->first();
        if (!$activeSeason) {
            return response()->json(['results' => []]);
        }

        $rows = DB::table('submissions')
            ->join('contestants', function ($join) use ($activeSeason) {
                $join->on('contestants.id', '=', 'submissions.contestant_id')
                    ->where('contestants.season_id', '=', $activeSeason->season_id);
            })
            ->join('users', 'users.id', '=', 'contestants.id')
            ->select(
                'submissions.contestant_id',
                'submissions.round',
                'submissions.md_group',
                'submissions.artist',
                'submissions.title',
                'submissions.url',
                DB::raw('COALESCE(users.global_name, users.username) as contestant_name')
            )
            ->where('submissions.season_id', $activeSeason->season_id)
            ->where('submissions.draft', false)
            ->distinct()
            ->get();

        $scored = [];
        foreach ($rows as $row) {
            $score = $this->matchScore($query, $row->artist, $row->title);
            if ($score !== null) {
                $scored[] = ['row' => $row, 'score' => $score];
            }
        }

        // Group identical songs (case/accent-insensitive) across rounds/contestants
        $grouped = [];
        foreach ($scored as $entry) {
            $row = $entry['row'];
            $key = $this->normalize($row->artist) . '|' . $this->normalize($row->title);

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'artist' => $row->artist,
                    'title' => $row->title,
                    'score' => $entry['score'],
                    'entries' => [],
                ];
            }

            $grouped[$key]['score'] = max($grouped[$key]['score'], $entry['score']);
            $grouped[$key]['entries'][] = [
                'contestant' => $row->contestant_name,
                'round' => $row->round,
                'group' => $this->groupLabel((int) $row->md_group),
                'url' => $row->url,
            ];
        }

        $grouped = array_values($grouped);
        usort($grouped, fn($a, $b) => $b['score'] <=> $a['score']);
        foreach ($grouped as &$g) {
            usort($g['entries'], fn($a, $b) => $a['round'] <=> $b['round']);
        }

        return response()->json(['results' => array_slice($grouped, 0, 30)]);
    }

    private function groupLabel(int $mdGroup): string
    {
        // Mirrors the mapping used in judging.blade.php (0->2, 1->3, 2->4, 3->5)
        return match ($mdGroup) {
            2 => 'Merge',
            3 => 'Group 1',
            4 => 'Group 2',
            5 => 'Group 3',
            default => 'Group ' . $mdGroup,
        };
    }

    /**
     * Highest score across artist / title / combined, or null if no match at all.
     */
    private function matchScore(string $query, string $artist, string $title): ?float
    {
        $q = $this->normalize($query);
        if ($q === '') {
            return null;
        }

        $best = null;
        foreach ([$this->normalize($artist), $this->normalize($title), $this->normalize($artist . ' ' . $title)] as $target) {
            $s = $this->scoreAgainst($q, $target);
            if ($s !== null && ($best === null || $s > $best)) {
                $best = $s;
            }
        }

        return $best;
    }

    private function scoreAgainst(string $q, string $target): ?float
    {
        if ($target === '') {
            return null;
        }

        // Substring match - strongest signal
        if (str_contains($target, $q)) {
            $lengthRatio = strlen($q) / max(strlen($target), 1);
            return 100 + ($lengthRatio * 20);
        }

        // Every word in the query shows up somewhere in the target
        $queryWords = array_values(array_filter(preg_split('/\s+/', $q)));
        if (count($queryWords) > 0) {
            $hits = 0;
            foreach ($queryWords as $w) {
                if (strlen($w) >= 2 && str_contains($target, $w)) {
                    $hits++;
                }
            }
            if ($hits === count($queryWords)) {
                return 60 + $hits;
            }
            if ($hits > 0 && ($hits / count($queryWords)) >= 0.5) {
                return 30 * ($hits / count($queryWords));
            }
        }

        // Typo tolerance
        $maxLen = max(strlen($q), strlen($target));
        if ($maxLen === 0) {
            return null;
        }
        $similarity = 1 - (levenshtein(substr($q, 0, 255), substr($target, 0, 255)) / $maxLen);

        return $similarity >= 0.6 ? $similarity * 25 : null;
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }
}