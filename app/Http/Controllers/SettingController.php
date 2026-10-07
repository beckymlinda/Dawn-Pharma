<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'system_name' => 'nullable|string|max:255',
            'location'    => 'nullable|string|max:500',
            'telephone'   => 'nullable|string|max:50',
            'primary_color'   => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'logo'    => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,ico,webp|max:1024',
        ]);

        // Save text/color settings
        $data = $request->except(['_token', '_method', 'logo', 'favicon']);
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            $oldLogo = Setting::where('key', 'logo')->first();
            if ($oldLogo && $oldLogo->value) {
                $oldPath = str_replace('storage/', '', $oldLogo->value);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('logo')->store('settings', 'public');
            Setting::updateOrCreate(['key' => 'logo'], ['value' => 'storage/' . $path]);
        }

        // Handle favicon upload
        if ($request->hasFile('favicon')) {
            // Delete old favicon if exists
            $oldFavicon = Setting::where('key', 'favicon')->first();
            if ($oldFavicon && $oldFavicon->value) {
                $oldPath = str_replace('storage/', '', $oldFavicon->value);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('favicon')->store('settings', 'public');
            Setting::updateOrCreate(['key' => 'favicon'], ['value' => 'storage/' . $path]);
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
