<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
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

        $venueSupported = Tournament::supportsVenue();
        $prizePoolSupported = Tournament::supportsPrizePool();
        $formatsSupported = Tournament::supportsFormats();

        return view('admin.tournaments.create', compact('venueSupported', 'prizePoolSupported', 'formatsSupported'));
    }

    /**
     * Store a tournament.
     *
     * No managers are assigned here: managers apply to open tournaments and
     * gain participation only when an admin approves their application.
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate($this->tournamentRules());

        $attributes = $this->tournamentAttributes($validated);

        // Only write the venue once the add_venue_to_tournaments migration has run,
        // so creating tournaments keeps working on an unmigrated database.
        if (Tournament::supportsVenue()) {
            $attributes['venue'] = $validated['venue'] ?? null;
        }

        // Same for the prize pool and its add_prize_pool_to_tournaments migration.
        if (Tournament::supportsPrizePool()) {
            $attributes['prize_pool'] = $validated['prize_pool'] ?? null;
        }

        // And for the formats and their add_formats_to_tournaments migration.
        if (Tournament::supportsFormats()) {
            $attributes['tournament_format'] = $validated['tournament_format'];
            $attributes['finals_format'] = $validated['finals_format'];
        }

        Tournament::create($attributes);

        return redirect()
            ->route('admin.tournaments.index')
            ->with(
                'success',
                'Tournament created successfully. Team managers can apply to take part while it is upcoming.'
            );
    }

    /**
     * Show the form to edit a tournament's details.
     */
    public function edit(Tournament $tournament)
    {
        $this->authorizeAdmin();

        $venueSupported = Tournament::supportsVenue();
        $prizePoolSupported = Tournament::supportsPrizePool();
        $formatsSupported = Tournament::supportsFormats();

        return view('admin.tournaments.edit', compact('tournament', 'venueSupported', 'prizePoolSupported', 'formatsSupported'));
    }

    /**
     * Update a tournament's details.
     *
     * Manager participation, teams, and games are left untouched.
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

        // The prize pool follows the same rule: omitted keeps it, blank clears it.
        if (Tournament::supportsPrizePool() && $request->exists('prize_pool')) {
            $attributes['prize_pool'] = $validated['prize_pool'] ?? null;
        }

        // Formats are required on every save, so a legacy tournament without
        // them gets real values the next time it is edited.
        if (Tournament::supportsFormats()) {
            $attributes['tournament_format'] = $validated['tournament_format'];
            $attributes['finals_format'] = $validated['finals_format'];
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
        // Required once the format columns exist; before their migration runs
        // the fields are not shown, so they are not validated either.
        $formatRules = Tournament::supportsFormats()
            ? [
                'tournament_format' => ['required', Rule::in(array_keys(Tournament::TOURNAMENT_FORMATS))],
                'finals_format' => ['required', Rule::in(array_keys(Tournament::FINALS_FORMATS))],
            ]
            : [];

        return $formatRules + [
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
            // Pesos; fits the decimal(12, 2) column without rounding.
            'prize_pool' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],
        ];
    }

    /**
     * Tournament columns (excluding venue, prize pool and formats) from validated input.
     *
     * Status is never taken from the request: the model derives it from
     * the start and end dates when the tournament is saved.
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
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ];
    }
}