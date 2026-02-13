<?php

namespace App\Http\Controllers;

use App\Models\Petition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PetitionController extends Controller
{
    public function index(): View
    {
        $petitions = Petition::latest()->get();
        return view('petitions.index', compact('petitions'));
    }

    public function create(): View
    {
        return view('petitions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'citizen_name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'string', 'max:30'],
            'petition_type' => ['required', 'string', 'max:100'],
            'remarks' => ['nullable', 'string'],
        ]);

        $prefix = now()->format('Y-m');
        $latest = Petition::where('reference_no', 'like', $prefix.'-%')
            ->orderBy('reference_no', 'desc')
            ->first();

        $seq = 1;
        if ($latest) {
            $parts = explode('-', $latest->reference_no);
            $lastSeq = intval(end($parts));
            $seq = $lastSeq + 1;
        }
        $referenceNo = $prefix.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

        Petition::create([
            'reference_no' => $referenceNo,
            'citizen_name' => $validated['citizen_name'],
            'mobile_number' => $validated['mobile_number'],
            'petition_type' => $validated['petition_type'],
            'status' => 'Filed',
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()->route('petitions.index')->with('status', 'Petition filed');
    }
}
