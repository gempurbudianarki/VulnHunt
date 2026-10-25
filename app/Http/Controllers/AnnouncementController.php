<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $announcements = Announcement::published()
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->paginate(10);

        return view('announcements.index', compact('announcements'));
    }
}
