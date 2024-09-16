<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use App\Models\Event;
use Carbon\Carbon;
use Exception;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the ability to upload a roster file.
     * This test ensures the file upload API works as expected.
     */
    public function test_can_upload_roster()
    {
        try {
            // Simulate a file upload with a fake HTML file.
            $response = $this->postJson('/api/upload-roster', [
                'roster' => UploadedFile::fake()->create('roster.html'),
            ]);

            // Assert the response is successful and matches the expected output.
            $response->assertStatus(200)->assertJson(['message' => 'Roster parsed successfully']);
        } catch (Exception $e) {
            // Handle any unexpected errors.
            $this->fail('Exception occurred while testing roster upload: ' . $e->getMessage());
        }
    }

    /**
     * Test the retrieval of events between specific dates.
     */
    public function test_can_get_events_between_dates()
    {
        try {
            // Create a sample event within the date range.
            Event::factory()->create(['start_time' => '2022-01-10 08:00:00', 'end_time' => '2022-01-10 12:00:00']);
            
            // Perform a GET request to fetch events between two dates.
            $response = $this->getJson('/api/events?start_date=2022-01-09&end_date=2022-01-11');
            
            // Assert the response contains exactly one event.
            $response->assertStatus(200)->assertJsonCount(1);
        } catch (Exception $e) {
            // Handle any unexpected errors.
            $this->fail('Exception occurred while testing event retrieval between dates: ' . $e->getMessage());
        }
    }

    /**
     * Test the retrieval of flight events for the next week.
     * The current date is set to January 14, 2022, and we expect flights in the next week to be returned.
     */
    public function test_can_get_flights_for_next_week()
    {
        try {
            // Set the current date to 14th January 2022 for test consistency.
            $currentDate = Carbon::create(2022, 1, 14);
            Carbon::setTestNow($currentDate);  // Set the current date for testing

            // Create a flight event within the next week.
            Event::factory()->create([
                'event_type' => 'FLT',
                'start_time' => '2022-01-16 08:00:00',
                'end_time' => '2022-01-16 10:00:00',
            ]);

            // Perform a GET request to fetch flights scheduled for the next week.
            $response = $this->getJson('/api/flights-next-week');

            // Assert the response contains exactly one flight event.
            $response->assertStatus(200)->assertJsonCount(1);
        } catch (Exception $e) {
            // Handle any unexpected errors.
            $this->fail('Exception occurred while testing flight retrieval for the next week: ' . $e->getMessage());
        }
    }

    /**
     * Test the retrieval of standby events for the next week.
     * The current date is set to January 14, 2022, and we expect standby events in the next week to be returned.
     */
    public function test_can_get_standby_events_for_next_week()
    {
        try {
            // Set the current date to 14th January 2022 for test consistency.
            $currentDate = Carbon::create(2022, 1, 14);
            Carbon::setTestNow($currentDate);  // Set the current date for testing

            // Create a standby event within the next week.
            Event::factory()->create([
                'event_type' => 'SBY',
                'start_time' => '2022-01-16 08:00:00',
                'end_time' => '2022-01-16 10:00:00',
            ]);

            // Perform a GET request to fetch standby events for the next week.
            $response = $this->getJson('/api/standby-next-week');

            // Assert the response contains exactly one standby event.
            $response->assertStatus(200)->assertJsonCount(1);
        } catch (Exception $e) {
            // Handle any unexpected errors.
            $this->fail('Exception occurred while testing standby retrieval for the next week: ' . $e->getMessage());
        }
    }

    /**
     * Test the retrieval of flights by location.
     * This test fetches flights departing from the specified location (e.g., JFK).
     */
    public function test_can_get_flights_by_location()
    {
        try {
            // Create a flight event departing from JFK.
            Event::factory()->create([
                'event_type' => 'FLT',
                'from' => 'JFK',
                'start_time' => '2022-01-15 08:00:00',
                'end_time' => '2022-01-15 10:00:00',
            ]);

            // Perform a GET request to fetch flights from a specific location (JFK).
            $response = $this->getJson('/api/flights-at-location?location=JFK');

            // Assert the response contains exactly one flight event.
            $response->assertStatus(200)->assertJsonCount(1);
        } catch (Exception $e) {
            // Handle any unexpected errors.
            $this->fail('Exception occurred while testing flight retrieval by location: ' . $e->getMessage());
        }
    }
}
