<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentSection;
use App\Models\Topic;
use App\Models\TopicOption;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Display the public home search page.
     */
    public function index(Request $request): View
    {
        $stats = [
            'total_topics' => Topic::query()->published()->count(),
            'total_options' => TopicOption::query()->published()->count(),
            'total_documents' => Document::query()->published()->count(),
            'total_sections' => DocumentSection::query()->count(),
        ];

        return view('home', [
            'stats' => $stats,
        ]);
    }
}
