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
                ->orderBy('date')
                ->orderBy('time')])
            ->latest()
            ->get();

        // Test/demo records can be accidentally left active in production.
        // Keep them out of the public homepage without changing the stored
        // records or any booking/admin behavior.
        $shows = $shows->reject(fn ($show) => preg_match(
            '/^(?:test|demo|sample|placeholder)(?:\b|[-_ ]|$)/i',
            trim((string) $show->title)
        ))->values();

        foreach ($shows as $show) {
            $upcomingTimes = $show->showTimes
                ->filter(function ($showTime) use ($now) {
                    if (!$showTime->date) {
                        return false;
                    }

                    try {
                        return Carbon::parse($showTime->date->format('Y-m-d') . ' ' . ($showTime->time ?? '00:00:00'))->gt($now);
                    } catch (\Throwable) {
                        return false;
                    }
                })
                ->values();

            // The public homepage lists only future performances and chooses
            // its lead production from the earliest real upcoming slot.
            $show->setRelation('showTimes', $upcomingTimes);
        }

        $featuredShow = $shows
            ->filter(fn ($show) => $show->showTimes->isNotEmpty())
            ->sortBy(fn ($show) => Carbon::parse(
                $show->showTimes->first()->date->format('Y-m-d') . ' ' . ($show->showTimes->first()->time ?? '00:00:00')
            )->timestamp)
            ->first();

        return view('shows.index', compact('shows', 'featuredShow'));
    }

    public function about()
    {
        $about = About::first();
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
