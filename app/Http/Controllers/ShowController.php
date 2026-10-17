<?php

namespace App\Http\Controllers;

use App\Models\Show;

class ShowController extends Controller
{
    public function index()
    {
        $shows = Show::where('is_active', true)->with('showTimes')->get()
            ->reject(fn (Show $show) => $show->isPublicPlaceholder());
        return view('shows.index', compact('shows'));
    }

    public function show(Show $show)
    {
        abort_if(!$show->is_active || $show->isPublicPlaceholder(), 404);
        $show->load('showTimes');
        return view('shows.show', compact('show'));
    }
}
