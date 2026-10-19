<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\Show;
use App\Models\About;
use Carbon\Carbon;

class SiteController extends Controller
{
    public function home()
    {
        $now = now();
        $shows = Show::where('is_active', true)
            ->with(['showTimes' => fn ($query) => $query
                ->whereDate('date', '>=', $now->toDateString())
                ->withSum(['bookings as homepage_reserved_tickets' => fn ($bookings) => $bookings
                    ->whereIn('status', ['approved', 'pending'])], 'tickets_count')
                ->orderBy('date')
                ->orderBy('time')])
            ->latest()
            ->get();

        // Test/demo records can be accidentally left active in production.
        // Keep them out of the public homepage without changing the stored
        // records or any booking/admin behavior.
        $shows = $shows->reject(fn ($show) => $show->isPublicPlaceholder())->values();

        foreach ($shows as $show) {
            $upcomingTimes = $show->showTimes
                ->filter(function ($showTime) use ($now) {
                    if (!$showTime->date) {
                        return false;
                    }

                    try {
                        $dateStr = $showTime->date instanceof \Carbon\CarbonInterface
                            ? $showTime->date->format('Y-m-d')
                            : (string) $showTime->date;
                        return Carbon::parse($dateStr . ' ' . ($showTime->time ?? '00:00:00'))->gt($now);
                    } catch (\Throwable) {
                        return false;
                    }
                })
                ->each(function ($showTime) {
                    // Match BookingController's authoritative capacity rule:
                    // pending and approved reservations consume inventory.
                    $reserved = (int) ($showTime->homepage_reserved_tickets ?? 0);
                    $showTime->setAttribute(
                        'homepage_remaining_tickets',
                        max(0, (int) $showTime->total_tickets - $reserved)
                    );
                })
                ->values();

            // The public homepage lists only future performances and chooses
            // its lead production from the earliest still-bookable slot.
            $show->setRelation('showTimes', $upcomingTimes);
        }

        $featuredShow = $shows
            ->filter(fn ($show) => $show->showTimes->contains(fn ($time) =>
                !$time->is_sold_out && $time->homepage_remaining_tickets > 0
            ))
            ->sortBy(function ($show) {
                $nextBookableTime = $show->showTimes->first(fn ($time) =>
                    !$time->is_sold_out && $time->homepage_remaining_tickets > 0
                );

                $dateStr = $nextBookableTime->date instanceof \Carbon\CarbonInterface
                    ? $nextBookableTime->date->format('Y-m-d')
                    : (string) $nextBookableTime->date;

                return Carbon::parse(
                    $dateStr . ' ' . ($nextBookableTime->time ?? '00:00:00')
                )->timestamp;
            })
            ->first();

        $about = About::first();

        return view('shows.index', compact('shows', 'featuredShow', 'about'));
    }

    public function about()
    {
        $about = About::first() ?? new About();
        return view('about', compact('about'));
    }

    // صفحة العروض السابقة (الكروت)
    public function archive()
    {
        $archives = Archive::latest()->get();
        return view('site.archive', compact('archives'));
    }

    // صفحة تفاصيل عرض سابق
    public function archiveShow(Archive $archive)
{
    $archive->load('images');

    return view('site.archive-show', compact('archive'));
}

}
