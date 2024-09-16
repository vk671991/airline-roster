<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\RosterParser;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Upload the roster file, validate it, and parse its content.
     * @param Request $request
     * @param RosterParser $parser
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadRoster(Request $request, RosterParser $parser)
    {
        try {
            // Validate the uploaded file (ensure it's an HTML file)
            $request->validate([
                'roster' => 'required|file|mimes:html,htm'
            ]);

            // Retrieve file content
            $file = $request->file('roster');
            $content = file_get_contents($file->getRealPath());

            // Parse the roster content using the RosterParser service
            $parser->parse($content);

            // Return success response if everything goes well
            return response()->json(['message' => 'Roster parsed successfully']);
        } catch (\Exception $e) {
            // Handle any exceptions and return an error response
            return response()->json(['error' => 'Failed to parse roster: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve events between two specified dates.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEvents(Request $request)
    {
        try {
            // Validate the input date range
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            // Extract query parameters
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');

            // Fetch events within the date range
            $events = Event::whereBetween('start_time', [$startDate, $endDate])->get();

            // Return the events
            return response()->json($events);
        } catch (\Exception $e) {
            // Handle any exceptions and return an error response
            return response()->json(['error' => 'Failed to retrieve events: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve flight events scheduled for the next week.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFlightsNextWeek()
    {
        try {
            // Get the current date and calculate the end of the next week
            $start = Carbon::create(2022, 1, 14);
            $end = $start->copy()->addWeek();

            // Fetch flight events within the next week
            $flights = Event::where('event_type', 'FLT')
                ->whereBetween('start_time', [$start, $end])
                ->get();

            // Return the flights
            return response()->json($flights);
        } catch (\Exception $e) {
            // Handle any exceptions and return an error response
            return response()->json(['error' => 'Failed to retrieve flights: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve standby events scheduled for the next week.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStandbyNextWeek()
    {
        try {
            // Get the current date and calculate the end of the next week
            $start = Carbon::create(2022, 1, 14);
            $end = $start->copy()->addWeek();

            // Fetch standby events within the next week
            $standbys = Event::where('event_type', 'SBY')
                ->whereBetween('start_time', [$start, $end])
                ->get();

            // Return the standby events
            return response()->json($standbys);
        } catch (\Exception $e) {
            // Handle any exceptions and return an error response
            return response()->json(['error' => 'Failed to retrieve standbys: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve flight events based on the specified location.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFlightsByLocation(Request $request)
    {
        try {
            // Validate the location parameter
            $request->validate([
                'location' => 'required|string',
            ]);

            // Extract location from the query
            $location = $request->query('location');
            $event_type = $request->query('event_type');

            // Fetch flight events originating from the specified location
            $flights = Event::where('event_type', $event_type)
                ->where('from', $location)
                ->get();

            // Return the flights
            return response()->json($flights);
        } catch (\Exception $e) {
            // Handle any exceptions and return an error response
            return response()->json(['error' => 'Failed to retrieve flights by location: ' . $e->getMessage()], 500);
        }
    }
}
