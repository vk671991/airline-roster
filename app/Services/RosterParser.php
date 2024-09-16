<?php 

namespace App\Services;

use App\Models\Event;
use Symfony\Component\DomCrawler\Crawler;
use Carbon\Carbon;
use Exception;

class RosterParser
{
    /**
     * Parses the HTML content to extract and store event data.
     *
     * @param string $htmlContent The HTML content to parse.
     * @return void
     */
    public function parse($htmlContent)
    {
        try {
            // Initialize the Crawler with the provided HTML content
            $crawler = new Crawler($htmlContent);

            // Extract relevant data from the table
            $crawler->filter('table tbody tr')->each(function (Crawler $row) {
                try {
                    // Safely retrieve text content using the first() method and avoid exceptions
                    $activity = $row->filter('.activitytablerow-activity')->count() ? $row->filter('.activitytablerow-activity')->text() : null;
                    $checkInUtc = $row->filter('.activitytablerow-checkinutc')->count() ? $row->filter('.activitytablerow-checkinutc')->text() : null;
                    $checkOutUtc = $row->filter('.activitytablerow-checkoututc')->count() ? $row->filter('.activitytablerow-checkoututc')->text() : null;

                    // Check if any of the required data is missing
                    if (!$activity || !$checkInUtc || !$checkOutUtc) {
                        // Skip this row if any required data is missing
                        return;
                    }

                    // Ensure times are valid before creating Carbon instances
                    $startTime = $this->isValidTime($checkInUtc) ? Carbon::createFromFormat('Hi', $checkInUtc, 'UTC') : null;
                    $endTime = $this->isValidTime($checkOutUtc) ? Carbon::createFromFormat('Hi', $checkOutUtc, 'UTC') : null;

                    if ($startTime && $endTime) {
                        $eventType = $this->getEventType($activity);
                        $from = $row->filter('.activitytablerow-fromstn')->count() ? $row->filter('.activitytablerow-fromstn')->text() : null;
                        $to = $row->filter('.activitytablerow-tostn')->count() ? $row->filter('.activitytablerow-tostn')->text() : null;
                        $flightNumber = $eventType === 'FLT' ? $this->extractFlightNumber($activity) : null;

                        // Create a new Event record in the database
                        Event::create([
                            'event_type' => $eventType,
                            'flight_number' => $flightNumber,
                            'from' => $from,
                            'to' => $to,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                        ]);
                    }
                } catch (Exception $e) {
                    // Log or handle the exception related to processing this row
                    // e.g., Log::error('Error processing row: ' . $e->getMessage());
                    // For now, we will just continue with the next row
                    // This ensures that an issue with one row doesn't stop the entire parsing process
                }
            });
        } catch (Exception $e) {
            // Log or handle the exception related to parsing the HTML content
            // e.g., Log::error('Error parsing HTML content: ' . $e->getMessage());
        }
    }

    /**
     * Validates if the time string is in the correct 'HHmm' format.
     *
     * @param string $time The time string to validate.
     * @return bool True if valid, false otherwise.
     */
    private function isValidTime($time)
    {
        // Check if the string is exactly 4 digits and numeric (e.g., 0745)
        return preg_match('/^\d{4}$/', $time);
    }

    /**
     * Determines the event type based on the activity text.
     *
     * @param string $activityText The activity text to analyze.
     * @return string The event type.
     */
    private function getEventType($activityText)
    {
        if (str_contains($activityText, 'OFF')) return 'DO';
        if (str_contains($activityText, 'SBY')) return 'SBY';
        if (preg_match('/^DX\d+/', $activityText)) return 'FLT';
        return 'UNK';
    }

    /**
     * Extracts the flight number from the activity text if present.
     *
     * @param string $activityText The activity text to extract the flight number from.
     * @return string|null The flight number or null if not found.
     */
    private function extractFlightNumber($activityText)
    {
        preg_match('/DX(\d+)/', $activityText, $matches);
        return $matches[1] ?? null;
    }
}
