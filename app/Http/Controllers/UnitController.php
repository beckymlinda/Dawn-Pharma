<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::paginate(10);
        return view('units.index', compact('units'));
    }

    public function create()
    {
        return view('units.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'required|string|max:50',
            'allow_decimal' => 'nullable|boolean'
        ]);

        Unit::create($data);
        return redirect()->route('units.index')->with('success', 'Unit created successfully!');
    }

    public function show(Unit $unit)
    {
        // No separate details page: open it for editing
        return redirect()->route('units.edit', $unit);
    }

    public function edit(Unit $unit)
    {
        return view('units.edit', compact('unit'));
    }

    public function update(Request $request, Unit $unit)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'required|string|max:50',
            'allow_decimal' => 'nullable|boolean'
        ]);

        $unit->update($data);
        return redirect()->route('units.index')->with('success', 'Unit updated successfully!');
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();
        return redirect()->route('units.index')->with('success', 'Unit deleted successfully!');
    }
}
