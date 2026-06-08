<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BookingStatusEnum;
use App\Enums\ReviewerTypeEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Pet;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PetReviewSeeder extends Seeder
{
    public function run(): void
    {
        $petOwner = User::where('email', 'user@orus.com')->first();
        if (! $petOwner) {
            throw new \RuntimeException('User user@orus.com not found. Run UsersSeeder first.');
        }

        $activity = Activity::first();
        if (! $activity) {
            throw new \RuntimeException('No activity found. Run ActivitySeeder first.');
        }

        $pets = Pet::whereIn('name', ['Rex', 'Minou', 'Luna', 'Caramel'])->pluck('id', 'name');
        if ($pets->isEmpty()) {
            throw new \RuntimeException('Pets not found. Run PetSeeder first.');
        }

        $hosts = [
            'lucy' => User::firstOrCreate(
                ['email' => 'lucy.moreau@kennelo.host'],
                ['first_name' => 'Lucy', 'last_name' => 'Moreau', 'password' => Hash::make('password'), 'email_verified_at' => now(), 'is_id_verified' => false],
            ),
            'thomas' => User::firstOrCreate(
                ['email' => 'thomas.blanc@kennelo.host'],
                ['first_name' => 'Thomas', 'last_name' => 'Blanc', 'password' => Hash::make('password'), 'email_verified_at' => now(), 'is_id_verified' => false],
            ),
            'sofia' => User::firstOrCreate(
                ['email' => 'sofia.chen@kennelo.host'],
                ['first_name' => 'Sofia', 'last_name' => 'Chen', 'password' => Hash::make('password'), 'email_verified_at' => now(), 'is_id_verified' => false],
            ),
            'marc' => User::firstOrCreate(
                ['email' => 'marc.petit@kennelo.host'],
                ['first_name' => 'Marc', 'last_name' => 'Petit', 'password' => Hash::make('password'), 'email_verified_at' => now(), 'is_id_verified' => false],
            ),
        ];

        $reviews = [
            ['pet' => 'Rex', 'host' => 'lucy', 'rating' => 5.0, 'comment' => 'Rex est un chien adorable, très bien élevé. Il s\'est parfaitement intégré avec les autres pensionnaires. On recommande vivement !', 'days_ago' => 45, 'nights' => 5],
            ['pet' => 'Rex', 'host' => 'thomas', 'rating' => 4.5, 'comment' => 'Très bon séjour pour Rex. Il est joueur et affectueux. Un peu agité le premier jour mais s\'est vite habitué. Propriétaire très attentif.', 'days_ago' => 90, 'nights' => 3],
            ['pet' => 'Rex', 'host' => 'sofia', 'rating' => 5.0, 'comment' => 'Rex a été un vrai bonheur à garder. Propre, obéissant et très affectueux. On serait ravis de l\'accueillir de nouveau !', 'days_ago' => 150, 'nights' => 7],
            ['pet' => 'Minou', 'host' => 'marc', 'rating' => 4.5, 'comment' => 'Minou est un chat très calme et indépendant. Il a pris ses marques rapidement. Très propre, aucun problème durant tout le séjour.', 'days_ago' => 30, 'nights' => 4],
            ['pet' => 'Minou', 'host' => 'lucy', 'rating' => 5.0, 'comment' => 'Adorable félin, pas de problème du tout. Minou s\'est montré très doux et a respecté les règles de la maison. Un séjour parfait.', 'days_ago' => 75, 'nights' => 6],
            ['pet' => 'Luna', 'host' => 'thomas', 'rating' => 4.5, 'comment' => 'Luna est une chatte pleine de vie et très curieuse. Elle s\'est bien adaptée à notre espace et était très sociable avec nous.', 'days_ago' => 20, 'nights' => 5],
            ['pet' => 'Luna', 'host' => 'sofia', 'rating' => 4.0, 'comment' => 'Séjour agréable avec Luna. Elle est un peu timide au départ mais devient très câline une fois en confiance. Très propre.', 'days_ago' => 60, 'nights' => 4],
            ['pet' => 'Caramel', 'host' => 'marc', 'rating' => 5.0, 'comment' => 'Caramel est un lapin doux et calme, un vrai bonheur à garder. Très propre dans sa cage et adore les caresses. On recommande à 100% !', 'days_ago' => 40, 'nights' => 3],
        ];

        foreach ($reviews as $data) {
            if (! $pets->has($data['pet'])) {
                continue;
            }

            $petId = $pets->get($data['pet']);
            $host = $hosts[$data['host']];
            $daysAgo = $data['days_ago'];
            $nights = $data['nights'];

            $booking = Booking::create([
                'user_id' => $petOwner->id,
                'activity_id' => $activity->id,
                'check_in_date' => Carbon::now()->subDays($daysAgo + $nights)->format('Y-m-d'),
                'check_out_date' => Carbon::now()->subDays($daysAgo)->format('Y-m-d'),
                'total_price' => $nights * 30.00,
                'status' => BookingStatusEnum::COMPLETED,
            ]);

            $booking->pets()->attach($petId, [
                'price_per_night' => 30.00,
                'number_of_nights' => $nights,
                'subtotal' => $nights * 30.00,
            ]);

            Review::create([
                'booking_id' => $booking->id,
                'reviewer_id' => $host->id,
                'reviewer_type' => ReviewerTypeEnum::ACTIVITY,
                'overall_rating' => $data['rating'],
                'comment' => $data['comment'],
                'would_recommend' => true,
                'is_published' => true,
                'published_at' => Carbon::now()->subDays($daysAgo - 1),
            ]);
        }
    }
}
