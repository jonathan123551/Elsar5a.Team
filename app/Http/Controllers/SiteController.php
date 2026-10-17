<?php

namespace App\Http\Controllers;

use App\Models\About;
use App\Models\Archive;
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
            $featuredShow,
            $featuredShowtime,
            $upcomingShowtimes,
            $scarcityState,
            $featuredPosterUrl,
            $isBookableNow,
        ] = $this->resolveFeaturedHomeShow();

        return view('welcome', compact(
            'featuredShow',
            'featuredShowtime',
            'upcomingShowtimes',
            'scarcityState',
            'featuredPosterUrl',
            'isBookableNow',
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
     * Resolve the current featured production for the homepage stage reveal.
     * Uses the canonical Show + ShowTime models.
     *
     * @return array{0: ?Show, 1: ?ShowTime, 2: Collection<int, ShowTime>, 3: array{level: string, label: string, remaining: ?int}, 4: ?string, 5: bool}
     */
    private function resolveFeaturedHomeShow(): array
    {
        $now = now();

        $activeShows = Show::where('is_active', true)
            ->with(['showTimes' => fn ($query) => $query->orderBy('date')->orderBy('time')])
            ->latest()
            ->get();

        // Keep placeholder shows out of the public homepage
        $activeShows = $activeShows->reject(fn ($show) => $show->isPublicPlaceholder())->values();

        if ($activeShows->isEmpty()) {
            return [
                null,
                null,
                collect(),
                ['level' => 'upcoming', 'label' => 'قريباً على الخشبة', 'remaining' => null],
                null,
                false,
            ];
        }

        // Prioritize active shows that have upcoming showtimes, ordered by the soonest upcoming showtime.
        $showsWithUpcoming = $activeShows->filter(function (Show $show) use ($now) {
            return $show->showTimes->contains(fn (ShowTime $st) => $st->starts_at && $st->starts_at->gte($now));
        })->sortBy(function (Show $show) use ($now) {
            return $show->showTimes
                ->filter(fn (ShowTime $st) => $st->starts_at && $st->starts_at->gte($now))
                ->min('starts_at');
        })->values();

        /** @var Show $featuredShow */
        $featuredShow = $showsWithUpcoming->first() ?? $activeShows->first();

        $upcomingShowtimes = $featuredShow->showTimes
            ->filter(fn (ShowTime $st) => $st->starts_at && $st->starts_at->gte($now))
            ->sortBy('starts_at')
            ->values();

        $bookableShowtimes = $upcomingShowtimes->filter(function (ShowTime $showtime) {
            return $showtime->isBookable();
        })->values();

        /** @var ?ShowTime $featuredShowtime */
        $featuredShowtime = $bookableShowtimes->first()
            ?? $upcomingShowtimes->first()
            ?? $featuredShow->showTimes->sortByDesc('starts_at')->first();

        $isBookableNow = $bookableShowtimes->isNotEmpty();

        if ($upcomingShowtimes->isEmpty()) {
            $scarcityState = [
                'level' => 'schedule_pending',
                'label' => 'عرض حالي — تابع المواعيد والتفاصيل',
                'remaining' => null,
            ];
        } else {
            $scarcityState = $this->computeProgressiveScarcity($featuredShowtime, $bookableShowtimes);
        }

        $featuredPosterUrl = $this->resolvePosterUrl($featuredShow->poster_path ?? $featuredShow->poster);

        return [
            $featuredShow,
            $featuredShowtime,
            $upcomingShowtimes,
            $scarcityState,
            $featuredPosterUrl,
            $isBookableNow,
        ];
    }

    /**
     * Normalize poster paths across full URLs, public assets, and storage disk paths.
     */
    public function resolvePosterUrl(?string $poster): ?string
    {
        if (! is_string($poster) || trim($poster) === '') {
            return null;
        }

        $poster = trim($poster);

        if (Str::startsWith($poster, ['http://', 'https://', '//', 'data:'])) {
            return $poster;
        }

        $clean = ltrim($poster, '/');

        if (Str::startsWith($clean, ['storage/', 'images/', 'assets/', 'build/'])) {
            return asset($clean);
        }

        if (file_exists(public_path($clean))) {
            return asset($clean);
        }

        return asset('storage/' . $clean);
    }

    /**
     * Progressive scarcity communication:
     * - normal (>20 seats) -> "متاح للحجز الآن"
     * - medium (11-20 seats) -> "متبقي عدد محدود من التذاكر"
     * - high (1-10 seats) -> "متبقي X تذاكر فقط"
     * - sold_out (0 seats across upcoming showtimes) -> "اكتمل العدد"
     *
     * @param  Collection<int, ShowTime>  $bookableShowtimes
     * @return array{level: string, label: string, remaining: ?int}
     */
    private function computeProgressiveScarcity(?ShowTime $featuredShowtime, Collection $bookableShowtimes): array
    {
        if (! $featuredShowtime || $bookableShowtimes->isEmpty()) {
            return [
                'level' => 'sold_out',
                'label' => 'اكتمل العدد',
                'remaining' => 0,
            ];
        }

        $remaining = (int) $featuredShowtime->remainingSeats();

        if ($remaining <= 0) {
            return [
                'level' => 'sold_out',
                'label' => 'اكتمل العدد',
                'remaining' => 0,
            ];
        }

        if ($remaining <= 10) {
            $label = $remaining === 1
                ? 'متبقي تذكرة واحدة فقط'
                : ($remaining === 2
                    ? 'متبقي تذكرتان فقط'
                    : "متبقي {$remaining} تذاكر فقط");

            return [
                'level' => 'high',
                'label' => $label,
                'remaining' => $remaining,
            ];
        }

        if ($remaining <= 20) {
            return [
                'level' => 'medium',
                'label' => 'متبقي عدد محدود من التذاكر',
                'remaining' => $remaining,
            ];
        }

        return [
            'level' => 'normal',
            'label' => 'متاح للحجز الآن',
            'remaining' => $remaining,
        ];
    }
}
