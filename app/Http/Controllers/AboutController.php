<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Topic;
use App\Models\TopicOption;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AboutController extends Controller
{
    /**
     * Display the About page.
     */
    public function index(Request $request): View
    {
        $stats = [
            'total_topics' => Topic::query()->published()->count(),
            'total_options' => TopicOption::query()->published()->count(),
            'total_tags' => Tag::query()->count(),
        ];

        return view('pages.about', [
            'stats' => $stats,
        ]);
    }
}
