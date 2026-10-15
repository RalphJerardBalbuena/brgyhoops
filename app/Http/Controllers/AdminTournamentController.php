<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminTournamentController extends Controller
{
    /**
     * Ensure only admins can manage tournaments.
     */
    private function authorizeAdmin(): void
    {
        abort_unless(
            auth()->check() && auth()->user()->role === 'admin',
            403,
            'Access denied. Administrators only.'
        );
    }

    /**
     * Display all tournaments.
     */
    public function index()
    {
        $this->authorizeAdmin();

        $tournaments = Tournament::withCount(['teams', 'games'])
            ->with('users')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.tournaments.index', compact('tournaments'));
    }

    /**
     * Show the form to create a tournament.
     */
    public function create()
    {
        $this->authorizeAdmin();

        $managers = User::where('role', 'team_manager')
            ->orderBy('name')
            ->get();

        $venueSupported = Tournament::supportsVenue();

        return view('admin.tournaments.create', compact('managers', 'venueSupported'));
    }

    /**
     * Store a tournament and assign team managers.
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate(array_merge($this->tournamentRules(), [
            'manager_ids' => ['nullable', 'array'],
            'manager_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')
                    ->where('role', 'team_manager'),
            ],
        ]));

        $attributes = $this->tournamentAttributes($validated);

        // Only write the venue once the add_venue_to_tournaments migration has run,
        // so creating tournaments keeps working on an unmigrated database.
        if (Tournament::supportsVenue()) {
            $attributes['venue'] = $validated['venue'] ?? null;
        }

        $tournament = Tournament::create($attributes);

        $tournament->users()->sync(
            $validated['manager_ids'] ?? []
        );

        return redirect()
            ->route('admin.tournaments.index')
            ->with(
                'success',
                'Tournament created and managers assigned successfully.'
            );
    }

    /**
     * Show the form to edit a tournament's details.
     */
    public function edit(Tournament $tournament)
    {
        $this->authorizeAdmin();

        $tournament->load('users')->loadCount(['teams', 'games']);

        $venueSupported = Tournament::supportsVenue();

        return view('admin.tournaments.edit', compact('tournament', 'venueSupported'));
    }

    /**
     * Update a tournament's details.
     *
     * Manager assignments, teams, and games are left untouched.
     */
    public function update(Request $request, Tournament $tournament)
    {
        $this->authorizeAdmin();

        $validated = $request->validate($this->tournamentRules());

        $attributes = $this->tournamentAttributes($validated);

        // Only touch the venue when the column exists and the form sent it,
        // so an omitted field never clears a stored venue. A submitted blank
        // venue clears it, matching the create form's optional behavior.
        if (Tournament::supportsVenue() && $request->exists('venue')) {
            $attributes['venue'] = $validated['venue'] ?? null;
        }

        $tournament->update($attributes);

        return redirect()
            ->route('admin.tournaments.index')
            ->with('success', 'Tournament updated successfully.');
    }

    /**
     * Validation rules shared by creating and updating a tournament.
     *
     * @return array<string, array<int, mixed>>
     */
    private function tournamentRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => [
                'required',
                Rule::in([
                    'inter_zone',
                    'inter_barangay',
                    'inter_town',
                ]),
            ],
            'venue' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'status' => [
                'required',
                Rule::in([
                    'upcoming',
                    'ongoing',
                    'completed',
                ]),
            ],
        ];
    }

    /**
     * Tournament columns (excluding venue) from validated input.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function tournamentAttributes(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'status' => $validated['status'],
        ];
    }
}