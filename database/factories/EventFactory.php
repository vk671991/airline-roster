<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition()
    {
        return [
            'event_type' => 'FLT',
            'flight_number' => $this->faker->regexify('[A-Z]{2}[0-9]{3}'),
            'from' => $this->faker->city,
            'to' => $this->faker->city,
            'start_time' => Carbon::now(),
            'end_time' => Carbon::now()->addHours(2),
        ];
    }
}


