<?php

namespace App\Http\Controllers;

use App\Models\About;
use App\Models\Archive;
use App\Models\Play;
use App\Models\Show;
use App\Models\ShowTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function home(): View
    {
        [
            'featuredShow' => $featuredShow,
            'featuredShowTime' => $featuredShowTime,
            'featuredRemainingSeats' => $featuredRemainingSeats,
            'featuredPrice' => $featuredPrice,
            'featuredPosterUrl' => $featuredPosterUrl,
            'upcomingShowTimesCount' => $upcomingShowTimesCount,
            'isFeaturedBookable' => $isFeaturedBookable,
        ] = $this->resolveFeaturedHomeShow();

        return view('welcome', compact(
            'featuredShow',
            'featuredShowTime',
            'featuredRemainingSeats',
            'featuredPrice',
            'featuredPosterUrl',
            'upcomingShowTimesCount',
            'isFeaturedBookable'
        ));
    }

    public function about(): View
    {
        $about = About::first();
        return view('about', compact('about'));
    }

    // صفحة العروض السابقة (الكروت)
    public function archive(): View
    {
        $archives = Archive::latest()->get();
        return view('site.archive', compact('archives'));
    }

    // صفحة تفاصيل عرض سابق
    public function archiveShow(Archive $archive): View
    {
        $archive->load('images');
        return view('site.archive-show', compact('archive'));
    }

    /**
     * Resolve the canonical current production for the homepage using the same
     * underlying Play + ShowTime data flow as ShowController@index.
     *
     * @return array{
     *     featuredShow: ?Play,
     *     featuredShowTime: ?ShowTime,
     *     featuredRemainingSeats: ?int,
     *     featuredPrice: ?float,
     *     featuredPosterUrl: ?string,
     *     upcomingShowTimesCount: int,
     *     isFeaturedBookable: bool
     * }
     */
    private function resolveFeaturedHomeShow(): array
    {
        $now = now();

        $activePlays = Play::where('is_active', true)
            ->with(['showTimes' => fn ($query) => $query->orderBy('date')->orderBy('time')])
            ->latest()
            ->get();

        // Keep placeholder shows out of the public homepage
        $activePlays = $activePlays->reject(fn ($show) => $show->isPublicPlaceholder())->values();

        if ($activePlays->isEmpty()) {
            return [
                'featuredShow' => null,
                'featuredShowTime' => null,
                'featuredRemainingSeats' => null,
                'featuredPrice' => null,
                'featuredPosterUrl' => null,
                'upcomingShowTimesCount' => 0,
                'isFeaturedBookable' => false,
            ];
        }

        // Prefer active plays that have upcoming non-cancelled showtimes, ordered by nearest upcoming date
        $playsWithUpcoming = $activePlays
            ->map(function (Play $play) use ($now) {
                $upcomingShowTimes = $play->showTimes
                    ->filter(fn (ShowTime $st) => $st->status !== 'cancelled' && $st->starts_at && $st->starts_at->gte($now))
                    ->values();

                $bookableUpcoming = $upcomingShowTimes->first(fn (ShowTime $st) => $st->isBookable());
                $primaryUpcoming = $bookableUpcoming ?? $upcomingShowTimes->first();

                return [
                    'play' => $play,
                    'primaryShowTime' => $primaryUpcoming,
                    'upcomingCount' => $upcomingShowTimes->count(),
                ];
            })
            ->filter(fn (array $item) => $item['primaryShowTime'] !== null)
            ->sortBy([
                fn (array $a, array $b) => ($b['primaryShowTime']->isBookable() <=> $a['primaryShowTime']->isBookable()),
                fn (array $a, array $b) => $a['primaryShowTime']->starts_at->timestamp <=> $b['primaryShowTime']->starts_at->timestamp,
            ])
            ->values();

        if ($playsWithUpcoming->isNotEmpty()) {
            $selected = $playsWithUpcoming->first();
            /** @var Play $featuredShow */
            $featuredShow = $selected['play'];
            /** @var ShowTime $featuredShowTime */
            $featuredShowTime = $selected['primaryShowTime'];
            $upcomingShowTimesCount = $selected['upcomingCount'];
        } else {
            // Fallback: pick the latest active play even if its showtimes are in the past or not yet scheduled
            /** @var Play $featuredShow */
            $featuredShow = $activePlays->first();
            /** @var Collection<int, ShowTime> $nonCancelled */
            $nonCancelled = $featuredShow->showTimes
                ->filter(fn (ShowTime $st) => $st->status !== 'cancelled')
                ->values();
            $featuredShowTime = $nonCancelled->last() ?? $featuredShow->showTimes->last();
            $upcomingShowTimesCount = 0;
        }

        $featuredRemainingSeats = $featuredShowTime ? $featuredShowTime->remainingSeats() : null;
        $featuredPrice = $featuredShowTime?->price ?? $featuredShow->effective_price;
        $featuredPosterUrl = $this->resolvePosterUrl($featuredShow->poster);
        $isFeaturedBookable = $featuredShowTime ? $featuredShowTime->isBookable() : false;

        return [
            'featuredShow' => $featuredShow,
            'featuredShowTime' => $featuredShowTime,
            'featuredRemainingSeats' => $featuredRemainingSeats,
            'featuredPrice' => $featuredPrice,
            'featuredPosterUrl' => $featuredPosterUrl,
            'upcomingShowTimesCount' => $upcomingShowTimesCount,
            'isFeaturedBookable' => $isFeaturedBookable,
        ];
    }

    /**
     * Safely resolve any stored poster path (full URL, public asset, or storage path).
     */
    public function resolvePosterUrl(?string $poster): ?string
    {
        if (! filled($poster)) {
            return null;
        }

        $trimmed = trim($poster);

        if (Str::startsWith($trimmed, ['http://', 'https://', '//', 'data:'])) {
            return $trimmed;
        }

        $normalized = ltrim($trimmed, '/');

        if (Str::startsWith($normalized, ['images/', 'storage/', 'build/', 'css/', 'js/'])) {
            return asset($normalized);
        }

        return asset('storage/' . $normalized);
    }
}
