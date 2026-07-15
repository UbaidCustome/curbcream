<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    private array $types = [
        'terms' => 'Terms & Conditions',
        'privacy' => 'Privacy Policy',
    ];

    public function index()
    {
        $contents = Content::whereIn('type', array_keys($this->types))
            ->get()
            ->keyBy('type');

        return view('admin.content.index', [
            'types' => $this->types,
            'contents' => $contents,
        ]);
    }

    public function update(Request $request, string $type)
    {
        if (!array_key_exists($type, $this->types)) {
            return back()->with('error', 'Invalid content type.');
        }

        $data = $request->validate([
            'description' => 'required|string',
        ]);

        Content::updateOrCreate(
            ['type' => $type],
            ['description' => $data['description']]
        );

        return back()->with('success', $this->types[$type] . ' updated.');
    }
}
