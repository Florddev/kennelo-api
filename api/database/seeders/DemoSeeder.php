<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ActivityPermissionEnum;
use App\Enums\ActivityStatusEnum;
use App\Enums\ActivityTypeEnum;
use App\Enums\AvailabilityStatusEnum;
use App\Enums\CollaboratorStatusEnum;
use App\Enums\WeekDayEnum;
use App\Models\Activity;
use App\Models\ActivityAvailability;
use App\Models\ActivityCycle;
use App\Models\ActivityCycleClosedWeekDay;
use App\Models\ActivityCycleSetting;
use App\Models\ActivityRole;
use App\Models\Address;
use App\Models\AnimalType;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Role;

class DemoSeeder extends Seeder
{
    private const PASSWORD = 'Soutenance2026!';

    private const SUMMER_MULTIPLIER = 1.2;

    private const WEEKEND_SURCHARGE = 4.0;

    private array $users = [
        'demo@kennelo.fr' => ['Camille', 'Dubois', 'manager'],
        'gestionnaire@kennelo.fr' => ['Alex', 'Martin', 'user'],
        'soigneur@kennelo.fr' => ['Sam', 'Petit', 'user'],
        'client1@kennelo.fr' => ['Léa', 'Moreau', 'user'],
        'client2@kennelo.fr' => ['Hugo', 'Bernard', 'user'],
        'client3@kennelo.fr' => ['Inès', 'Laurent', 'user'],
        'client4@kennelo.fr' => ['Nathan', 'Roux', 'user'],
    ];

    private array $pensions = [
        'palace' => [
            'name' => 'Le Palace des Crocs',
            'description' => 'Pension canine haut de gamme au coeur du 11e : boxes spacieux, cour arborée et balades quotidiennes en meute au bois de Vincennes.',
            'phone' => '+33142050101',
            'address' => ['12 Rue de la Roquette', '75011', 48.85463, 2.37138],
            'animals' => ['dog' => [12, 32.0]],
            'closed_weekdays' => 64,
            'closed_offsets' => [10, 11],
            'services' => [
                ['Balade en meute au bois de Vincennes', ['dog'], true, null],
                ['Séance d\'éducation canine', ['dog'], false, 25.0],
                ['Toilettage complet', ['dog'], false, 35.0],
            ],
        ],
        'moustaches' => [
            'name' => 'Les Moustaches de Montmartre',
            'description' => 'Chatterie familiale sur la butte Montmartre : chambres individuelles vitrées, arbres à chat et coin soleil pour siestes prolongées.',
            'phone' => '+33142050202',
            'address' => ['24 Rue des Abbesses', '75018', 48.88459, 2.33865],
            'animals' => ['cat' => [10, 24.0]],
            'closed_weekdays' => 0,
            'closed_offsets' => [18],
            'services' => [
                ['Brossage quotidien', ['cat'], true, null],
                ['Séance de jeu individuelle', ['cat'], false, 8.0],
            ],
        ],
        'clapier' => [
            'name' => 'Le Clapier du Marais',
            'description' => 'Refuge douillet pour lapins et rongeurs : enclos individuels, foin à volonté et légumes frais du marché des Enfants-Rouges.',
            'phone' => '+33142050303',
            'address' => ['8 Rue des Rosiers', '75004', 48.85712, 2.36112],
            'animals' => ['rabbit' => [8, 18.0], 'rodent' => [10, 12.0]],
            'closed_weekdays' => 0,
            'closed_offsets' => [21, 22],
            'services' => [
                ['Foin et légumes frais à volonté', ['rabbit', 'rodent'], true, null],
                ['Taille de griffes', ['rabbit', 'rodent'], false, 10.0],
            ],
        ],
        'voliere' => [
            'name' => 'La Volière de Belleville',
            'description' => 'Pension pour oiseaux avec volière de vol libre, perchoirs naturels et suivi alimentaire adapté à chaque espèce.',
            'phone' => '+33142050404',
            'address' => ['47 Rue de Belleville', '75020', 48.87218, 2.38189],
            'animals' => ['bird' => [15, 14.0]],
            'closed_weekdays' => 0,
            'closed_offsets' => [13],
            'services' => [
                ['Vol libre quotidien en volière', ['bird'], true, null],
                ['Taille des plumes et des griffes', ['bird'], false, 12.0],
            ],
        ],
        'terrarium' => [
            'name' => 'Le Terrarium du Jardin des Plantes',
            'description' => 'Pension spécialisée reptiles et amphibiens : terrariums calibrés, contrôle UVB et hygrométrie surveillée jour et nuit.',
            'phone' => '+33142050505',
            'address' => ['15 Rue Linné', '75005', 48.84486, 2.35403],
            'animals' => ['reptile' => [10, 16.0], 'amphibian' => [6, 13.0]],
            'closed_weekdays' => 1,
            'closed_offsets' => [8, 9],
            'services' => [
                ['Contrôle hygrométrie et UVB', ['reptile', 'amphibian'], true, null],
                ['Nettoyage complet du terrarium', ['reptile', 'amphibian'], false, 15.0],
            ],
        ],
        'furets' => [
            'name' => 'Furets & Galipettes',
            'description' => 'Aux Batignolles, pension dédiée aux furets et petits rongeurs : parcours de jeu, tunnels et hamacs pour siestes acrobatiques.',
            'phone' => '+33142050606',
            'address' => ['66 Rue des Dames', '75017', 48.88421, 2.32115],
            'animals' => ['ferret' => [8, 17.0], 'rodent' => [8, 12.0]],
            'closed_weekdays' => 0,
            'closed_offsets' => [26],
            'services' => [
                ['Parcours de jeu et tunnels', ['ferret', 'rodent'], true, null],
                ['Bain et soin du pelage', ['ferret'], false, 18.0],
            ],
        ],
    ];

    private array $pets = [
        'client1@kennelo.fr' => [
            ['Oslo', 'dog', 'Berger australien', 'male', 24.0, 'Très sociable, adore les longues balades.'],
            ['Pixel', 'cat', 'Européen', 'female', 4.2, 'Dort beaucoup, joueuse en fin de journée.'],
        ],
        'client2@kennelo.fr' => [
            ['Caramel', 'rabbit', 'Bélier nain', 'male', 1.8, 'Gourmand, raffole des fanes de carottes.'],
            ['Noisette', 'rodent', 'Hamster doré', 'female', 0.12, 'Active la nuit, roue indispensable.'],
        ],
        'client3@kennelo.fr' => [
            ['Rio', 'bird', 'Gris du Gabon', 'male', 0.45, 'Très bavard, imite la sonnette.'],
            ['Gaïa', 'reptile', 'Gecko léopard', 'female', 0.06, 'Besoin d\'un point chaud à 32 degrés.'],
        ],
        'client4@kennelo.fr' => [
            ['Jack', 'ferret', 'Furet putoisé', 'male', 1.2, 'Espiègle, cache tout ce qui brille.'],
            ['Torpille', 'rodent', 'Rat domestique', 'male', 0.35, 'Curieux, très à l\'aise avec les humains.'],
        ],
    ];

    private array $bookings = [
        ['palace-pending', 'client1@kennelo.fr', 'palace', 'pending', ['Oslo'], 6, 5, 'Oslo n\'a jamais dormi en pension, un box calme serait idéal.', 'pending'],
        ['voliere-pending', 'client3@kennelo.fr', 'voliere', 'pending', ['Rio'], 9, 4, 'Rio répète tout ce qu\'il entend, ne soyez pas surpris.', 'pending'],
        ['clapier-confirmed', 'client2@kennelo.fr', 'clapier', 'confirmed', ['Caramel', 'Noisette'], 5, 7, 'Prévoir des enclos séparés pour les repas.', 'succeeded'],
        ['furets-confirmed', 'client4@kennelo.fr', 'furets', 'confirmed', ['Jack'], 20, 4, null, 'succeeded'],
        ['moustaches-inprogress', 'client1@kennelo.fr', 'moustaches', 'in_progress', ['Pixel'], -2, 6, 'Pixel dort beaucoup la journée, c\'est normal.', 'succeeded'],
        ['furets-completed', 'client4@kennelo.fr', 'furets', 'completed', ['Jack', 'Torpille'], -25, 5, null, 'succeeded'],
        ['clapier-completed', 'client2@kennelo.fr', 'clapier', 'completed', ['Caramel'], -40, 3, null, 'succeeded'],
        ['terrarium-cancelled', 'client3@kennelo.fr', 'terrarium', 'cancelled', ['Gaïa'], 14, 5, null, 'refunded'],
        ['palace-rejected', 'client1@kennelo.fr', 'palace', 'rejected', ['Oslo'], -12, 3, 'Oslo peut être bruyant la nuit.', 'refunded'],
        ['voliere-expired', 'client3@kennelo.fr', 'voliere', 'expired', ['Rio'], -8, 3, null, 'canceled'],
        ['palace-review-1', 'client1@kennelo.fr', 'palace', 'completed', ['Oslo'], -60, 4, null, 'succeeded'],
        ['palace-review-2', 'client1@kennelo.fr', 'palace', 'completed', ['Oslo'], -90, 6, null, 'succeeded'],
        ['moustaches-review-1', 'client1@kennelo.fr', 'moustaches', 'completed', ['Pixel'], -50, 5, null, 'succeeded'],
        ['moustaches-review-2', 'client1@kennelo.fr', 'moustaches', 'completed', ['Pixel'], -75, 3, null, 'succeeded'],
        ['clapier-review-1', 'client2@kennelo.fr', 'clapier', 'completed', ['Caramel', 'Noisette'], -70, 4, null, 'succeeded'],
        ['clapier-review-2', 'client4@kennelo.fr', 'clapier', 'completed', ['Torpille'], -55, 3, null, 'succeeded'],
        ['voliere-review-1', 'client3@kennelo.fr', 'voliere', 'completed', ['Rio'], -65, 5, null, 'succeeded'],
        ['voliere-review-2', 'client3@kennelo.fr', 'voliere', 'completed', ['Rio'], -95, 3, null, 'succeeded'],
        ['terrarium-review-1', 'client3@kennelo.fr', 'terrarium', 'completed', ['Gaïa'], -45, 6, null, 'succeeded'],
        ['terrarium-review-2', 'client3@kennelo.fr', 'terrarium', 'completed', ['Gaïa'], -80, 4, null, 'succeeded'],
        ['furets-review-1', 'client4@kennelo.fr', 'furets', 'completed', ['Jack'], -85, 4, null, 'succeeded'],
        ['furets-review-2', 'client2@kennelo.fr', 'furets', 'completed', ['Noisette'], -35, 3, null, 'succeeded'],
    ];

    private array $reviews = [
        'palace-review-1' => [4.9, 'Oslo est revenu épuisé de bonheur, les balades quotidiennes au bois de Vincennes ont fait toute la différence.', true],
        'palace-review-2' => [4.7, 'Équipe aux petits soins, photos envoyées chaque soir et box impeccable. On reviendra !', true],
        'moustaches-review-1' => [5.0, 'Pixel a eu droit à sa chambre vitrée côté soleil, elle n\'avait presque pas envie de rentrer à la maison.', true],
        'moustaches-review-2' => [4.2, 'Très bonne garde, juste un peu d\'attente au moment du dépôt le samedi matin.', true],
        'clapier-completed' => [4.8, 'Caramel a été chouchouté et son foin de Crau était bien au rendez-vous. Merci !', true],
        'clapier-review-1' => [4.6, 'Enclos séparés comme demandé et légumes frais tous les jours, rien à redire.', true],
        'clapier-review-2' => [4.4, 'Torpille est revenu détendu, communication très réactive pendant tout le séjour.', true],
        'voliere-review-1' => [4.9, 'Rio a adoré la volière de sociabilisation, il est même revenu avec deux nouveaux mots au répertoire.', true],
        'voliere-review-2' => [3.6, 'Équipe sérieuse mais la volière commune était un peu bruyante pour mon perroquet, séjour correct sans plus.', false],
        'terrarium-review-1' => [5.0, 'Hygrométrie surveillée de près, Gaïa était en pleine forme au retour. De vrais pros du reptile.', true],
        'terrarium-review-2' => [4.7, 'Installation impeccable et conseils précieux sur l\'éclairage UVB, je recommande.', true],
        'furets-completed' => [4.8, 'Jack et Torpille ont profité des tunnels et des hamacs, séjour parfait du début à la fin.', true],
        'furets-review-1' => [4.6, 'Jack revient toujours heureux, le parcours de jeu est une merveille pour les furets.', true],
        'furets-review-2' => [4.3, 'Bonne pension pour ma petite ratoune, personnel attentionné et local très propre.', true],
    ];

    private array $reviewCriteriaCodes = [
        'cleanliness',
        'communication',
        'animal_care',
        'instructions_respect',
        'value_for_money',
        'environment',
        'reactivity',
    ];

    public function run(): void
    {
        $userModels = $this->seedUsers();
        $manager = $userModels['demo@kennelo.fr'];

        $animalTypes = AnimalType::pluck('id', 'code');
        $activities = $this->seedActivities($manager);
        $this->seedCycles($activities, $animalTypes);
        $this->seedServices($activities, $animalTypes);
        $this->seedAvailabilities($activities);
        $this->seedCollaborators($activities, $userModels);
        $petModels = $this->seedPets($userModels, $animalTypes);
        $bookingIds = $this->seedBookings($userModels, $activities, $petModels);
        $this->seedConversations($userModels, $activities, $bookingIds);
        $this->seedReviews($userModels, $bookingIds);
    }

    private function uid(string $key): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, 'kennelo:demo:'.$key)->toString();
    }

    private function upsertKeepingId(string $table, array $attributes, array $values, string $newId): void
    {
        if (DB::table($table)->where($attributes)->exists()) {
            DB::table($table)->where($attributes)->update($values);

            return;
        }

        DB::table($table)->insert(array_merge($attributes, $values, ['id' => $newId]));
    }

    private function seedUsers(): array
    {
        $models = [];

        foreach ($this->users as $email => [$firstName, $lastName, $role]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password' => Hash::make(self::PASSWORD),
                    'is_id_verified' => true,
                ]
            );

            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            if (Role::where('name', $role)->exists() && ! $user->hasRole($role)) {
                $user->assignRole($role);
            }

            $models[$email] = $user;
        }

        return $models;
    }

    private function seedActivities(User $manager): array
    {
        $activities = [];

        foreach ($this->pensions as $key => $pension) {
            [$line1, $postalCode, $latitude, $longitude] = $pension['address'];

            $address = Address::firstOrCreate(
                ['line1' => $line1, 'postal_code' => $postalCode],
                [
                    'city' => 'Paris',
                    'region' => 'Île-de-France',
                    'department' => '75',
                    'country' => 'FR',
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]
            );

            $activities[$key] = Activity::firstOrCreate(
                ['name' => $pension['name'], 'manager_id' => $manager->id],
                [
                    'description' => $pension['description'],
                    'phone' => $pension['phone'],
                    'email' => 'contact+'.$key.'@kennelo.fr',
                    'type' => ActivityTypeEnum::BOARDING->value,
                    'status' => ActivityStatusEnum::APPROVED->value,
                    'is_active' => true,
                    'timezone' => 'Europe/Paris',
                    'address_id' => $address->id,
                ]
            );
        }

        return $activities;
    }

    private function seedCycles(array $activities, $animalTypes): void
    {
        foreach ($this->pensions as $key => $pension) {
            $activity = $activities[$key];

            $standard = ActivityCycle::firstOrCreate(
                ['activity_id' => $activity->id, 'priority' => 0],
                ['start_date' => null, 'end_date' => null, 'is_active' => true]
            );

            $summer = ActivityCycle::firstOrCreate(
                ['activity_id' => $activity->id, 'priority' => 1],
                [
                    'start_date' => '2026-07-01',
                    'end_date' => '2026-08-31',
                    'is_active' => true,
                    'color' => '#F59E0B',
                ]
            );

            if ($pension['closed_weekdays'] > 0) {
                ActivityCycleClosedWeekDay::updateOrCreate(
                    ['activity_cycle_id' => $standard->id],
                    ['sum_weekdays' => $pension['closed_weekdays']]
                );
            }

            foreach ($pension['animals'] as $code => [$capacity, $basePrice]) {
                $this->seedCycleSetting($standard, $animalTypes[$code], $capacity, $basePrice, 1.0);
                $this->seedCycleSetting($summer, $animalTypes[$code], $capacity, $basePrice, self::SUMMER_MULTIPLIER);
            }
        }
    }

    private function seedCycleSetting(ActivityCycle $cycle, string $animalTypeId, int $capacity, float $basePrice, float $multiplier): void
    {
        $setting = ActivityCycleSetting::firstOrCreate(
            ['activity_cycle_id' => $cycle->id, 'animal_type_id' => $animalTypeId],
            ['max_capacity' => $capacity]
        );

        $weekend = WeekDayEnum::SATURDAY->value | WeekDayEnum::SUNDAY->value;

        foreach (WeekDayEnum::values() as $weekday) {
            $price = ($weekday & $weekend) !== 0 ? $basePrice + self::WEEKEND_SURCHARGE : $basePrice;

            $this->upsertKeepingId(
                'activities_cycles_settings_prices',
                ['activity_cycle_setting_id' => $setting->id, 'weekday' => $weekday],
                [
                    'price' => round($price * $multiplier, 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                $this->uid('price:'.$setting->id.':'.$weekday)
            );
        }
    }

    private function seedServices(array $activities, $animalTypes): void
    {
        foreach ($this->pensions as $key => $pension) {
            foreach ($pension['services'] as [$name, $codes, $isIncluded, $price]) {
                foreach ($codes as $code) {
                    Service::firstOrCreate(
                        [
                            'activity_id' => $activities[$key]->id,
                            'animal_type_id' => $animalTypes[$code],
                            'name' => $name,
                        ],
                        ['is_included' => $isIncluded, 'price' => $price]
                    );
                }
            }
        }
    }

    private function seedAvailabilities(array $activities): void
    {
        $startDate = CarbonImmutable::now()->startOfMonth();
        $endDate = CarbonImmutable::now()->addMonths(3)->endOfMonth();

        foreach ($this->pensions as $key => $pension) {
            $activity = $activities[$key];

            $closedDates = array_map(
                fn (int $offset): string => CarbonImmutable::now()->addDays($offset)->toDateString(),
                $pension['closed_offsets']
            );

            for ($date = $startDate; $date->lte($endDate); $date = $date->addDay()) {
                $isClosed = in_array($date->toDateString(), $closedDates, true);

                ActivityAvailability::updateOrCreate(
                    ['activity_id' => $activity->id, 'date' => $date->toDateString()],
                    [
                        'status' => $isClosed ? AvailabilityStatusEnum::CLOSED : AvailabilityStatusEnum::OPEN,
                        'note' => $isClosed ? 'Fermeture exceptionnelle' : null,
                    ]
                );
            }
        }
    }

    private function seedCollaborators(array $activities, array $userModels): void
    {
        $allPermissions = ActivityPermissionEnum::values();
        $caretakerPermissions = [
            ActivityPermissionEnum::MANAGE_AVAILABILITIES->value,
            ActivityPermissionEnum::MANAGE_BOOKINGS->value,
        ];

        foreach ($activities as $key => $activity) {
            $this->attachCollaborator($activity, $userModels['gestionnaire@kennelo.fr'], 'Gestionnaire', $allPermissions);

            if (in_array($key, ['palace', 'moustaches'], true)) {
                $this->attachCollaborator($activity, $userModels['soigneur@kennelo.fr'], 'Soigneur', $caretakerPermissions);
            }
        }
    }

    private function attachCollaborator(Activity $activity, User $user, string $roleName, array $permissions): void
    {
        $role = ActivityRole::firstOrCreate(['activity_id' => $activity->id, 'name' => $roleName]);

        foreach ($permissions as $permission) {
            $this->upsertKeepingId(
                'activity_role_permissions',
                ['role_id' => $role->id, 'permission' => $permission],
                ['created_at' => now(), 'updated_at' => now()],
                $this->uid('permission:'.$role->id.':'.$permission)
            );
        }

        DB::table('activity_collaborators')->updateOrInsert(
            ['activity_id' => $activity->id, 'user_id' => $user->id],
            [
                'role_id' => $role->id,
                'status' => CollaboratorStatusEnum::ACCEPTED->value,
                'invited_at' => now()->subDays(30),
                'responded_at' => now()->subDays(29),
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(29),
            ]
        );
    }

    private function seedPets(array $userModels, $animalTypes): array
    {
        $models = [];

        foreach ($this->pets as $email => $pets) {
            foreach ($pets as [$name, $code, $breed, $sex, $weight, $about]) {
                $models[$name] = Pet::firstOrCreate(
                    ['user_id' => $userModels[$email]->id, 'name' => $name],
                    [
                        'animal_type_id' => $animalTypes[$code],
                        'breed' => $breed,
                        'sex' => $sex,
                        'weight' => $weight,
                        'about' => $about,
                        'birth_date' => now()->subYears(3)->toDateString(),
                        'is_sterilized' => true,
                        'has_microchip' => false,
                    ]
                );
            }
        }

        return $models;
    }

    private function seedBookings(array $userModels, array $activities, array $petModels): array
    {
        $petTypes = [];
        foreach ($this->pets as $pets) {
            foreach ($pets as [$name, $code]) {
                $petTypes[$name] = $code;
            }
        }

        $bookingIds = [];

        foreach ($this->bookings as [$key, $email, $pensionKey, $status, $petNames, $startOffset, $nights, $requests, $paymentStatus]) {
            $bookingId = $this->uid('booking:'.$key);
            $bookingIds[$key] = $bookingId;

            $checkIn = CarbonImmutable::now()->addDays($startOffset)->startOfDay();
            $checkOut = $checkIn->addDays($nights);
            $createdAt = $checkIn->subDays(7);

            $total = 0.0;
            $petRows = [];

            foreach ($petNames as $petName) {
                $pricePerNight = $this->pensions[$pensionKey]['animals'][$petTypes[$petName]][1];
                $subtotal = $pricePerNight * $nights;
                $total += $subtotal;

                $petRows[] = [
                    'pet_id' => $petModels[$petName]->id,
                    'price_per_night' => $pricePerNight,
                    'number_of_nights' => $nights,
                    'subtotal' => $subtotal,
                ];
            }

            DB::table('bookings')->updateOrInsert(
                ['id' => $bookingId],
                [
                    'user_id' => $userModels[$email]->id,
                    'activity_id' => $activities[$pensionKey]->id,
                    'check_in_date' => $checkIn->toDateString(),
                    'check_out_date' => $checkOut->toDateString(),
                    'total_price' => $total,
                    'status' => $status,
                    'payment_status' => $paymentStatus,
                    'paid_at' => $paymentStatus === 'succeeded' ? $createdAt : null,
                    'refunded_at' => $paymentStatus === 'refunded' ? $createdAt->addDays(2) : null,
                    'special_requests' => $requests,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );

            foreach ($petRows as $row) {
                DB::table('booking_pets')->updateOrInsert(
                    ['booking_id' => $bookingId, 'pet_id' => $row['pet_id']],
                    [
                        'price_per_night' => $row['price_per_night'],
                        'number_of_nights' => $row['number_of_nights'],
                        'subtotal' => $row['subtotal'],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]
                );
            }
        }

        return $bookingIds;
    }

    private function seedReviews(array $userModels, array $bookingIds): void
    {
        $bookingsByKey = [];
        foreach ($this->bookings as $booking) {
            $bookingsByKey[$booking[0]] = $booking;
        }

        $scoreOffsets = [0.0, 0.2, -0.3, 0.1, -0.2, 0.2, 0.0];

        foreach ($this->reviews as $bookingKey => [$rating, $comment, $wouldRecommend]) {
            [, $email, , , , $startOffset, $nights] = $bookingsByKey[$bookingKey];
            $publishedAt = CarbonImmutable::now()->addDays($startOffset + $nights + 2);
            $reviewId = $this->uid('review:'.$bookingKey);

            $this->upsertKeepingId(
                'reviews',
                ['booking_id' => $bookingIds[$bookingKey], 'reviewer_type' => 'user'],
                [
                    'reviewer_id' => $userModels[$email]->id,
                    'overall_rating' => $rating,
                    'comment' => $comment,
                    'would_recommend' => $wouldRecommend,
                    'is_published' => true,
                    'published_at' => $publishedAt,
                    'created_at' => $publishedAt,
                    'updated_at' => $publishedAt,
                ],
                $reviewId
            );

            $reviewId = DB::table('reviews')
                ->where('booking_id', $bookingIds[$bookingKey])
                ->where('reviewer_type', 'user')
                ->value('id');

            foreach ($this->reviewCriteriaCodes as $index => $code) {
                $score = round(min(5.0, max(3.0, $rating + $scoreOffsets[$index])), 1);

                $this->upsertKeepingId(
                    'review_criteria_scores',
                    ['review_id' => $reviewId, 'criteria_code' => $code],
                    ['score' => $score, 'created_at' => $publishedAt],
                    $this->uid('score:'.$bookingKey.':'.$code)
                );
            }
        }
    }

    private function seedConversations(array $userModels, array $activities, array $bookingIds): void
    {
        $threads = [
            [
                'client1@kennelo.fr', 'palace', 'palace-pending', true,
                [
                    ['user', -8, 'Bonjour ! Oslo, mon berger australien, n\'a jamais dormi en pension. Comment se passent les premières nuits ?'],
                    ['activity', -8, 'Bonjour Léa ! Nous prévoyons toujours une arrivée en douceur : box calme, balade d\'intégration et petit rapport photo le premier soir.'],
                    ['user', -6, 'Parfait, je viens d\'envoyer ma demande de réservation pour Oslo.', 'palace-pending'],
                ],
            ],
            [
                'client3@kennelo.fr', 'voliere', 'voliere-pending', true,
                [
                    ['user', -3, 'Bonjour, Rio est un gris du Gabon très bavard qui a besoin de compagnie. Vos volières sont-elles partagées ?'],
                    ['activity', -3, 'Bonjour Inès ! Oui, nous avons une volière de sociabilisation supervisée, Rio y trouvera de la conversation.'],
                    ['user', -2, 'Super, demande envoyée pour Rio !', 'voliere-pending'],
                ],
            ],
            [
                'client2@kennelo.fr', 'clapier', 'clapier-confirmed', true,
                [
                    ['user', -10, 'Bonjour, Caramel ne mange que du foin de Crau. Est-ce un problème ?'],
                    ['activity', -10, 'Bonjour Hugo, aucun souci : apportez son foin et nous complétons avec nos légumes frais.'],
                    ['activity', -2, 'Votre réservation pour Caramel et Noisette est confirmée, à très vite !', 'clapier-confirmed'],
                ],
            ],
            [
                'client4@kennelo.fr', 'furets', 'furets-completed', false,
                [
                    ['user', -33, 'Bonjour, Jack et Torpille peuvent-ils partager le même espace de jeu ?'],
                    ['activity', -33, 'Bonjour Nathan, bien sûr ! Ils profiteront du parcours de tunnels ensemble sous surveillance.'],
                    ['activity', -20, 'Le séjour de Jack et Torpille s\'est très bien passé, merci de votre confiance !', 'furets-completed'],
                ],
            ],
        ];

        foreach ($threads as [$email, $pensionKey, $bookingKey, $isActive, $messages]) {
            $userId = $userModels[$email]->id;
            $activity = $activities[$pensionKey];
            $managerId = $activity->manager_id;
            $conversationId = $this->uid('conversation:'.$email.':'.$pensionKey);

            $lastOffset = end($messages)[1];

            $existingConversationId = DB::table('conversations')
                ->where('user_id', $userId)
                ->where('activity_id', $activity->id)
                ->value('id');

            if ($existingConversationId !== null) {
                $conversationId = $existingConversationId;
                DB::table('conversations')->where('id', $conversationId)->update([
                    'last_message_at' => now()->addDays($lastOffset),
                    'updated_at' => now()->addDays($lastOffset),
                ]);
            } else {
                DB::table('conversations')->insert([
                    'id' => $conversationId,
                    'user_id' => $userId,
                    'activity_id' => $activity->id,
                    'last_message_at' => now()->addDays($lastOffset),
                    'created_at' => now()->addDays($messages[0][1]),
                    'updated_at' => now()->addDays($lastOffset),
                ]);
            }

            foreach ($messages as $index => $message) {
                [$senderType, $dayOffset, $content] = $message;
                $referencedBookingKey = $message[3] ?? null;
                $sentAt = now()->addDays($dayOffset)->addMinutes($index * 17);

                DB::table('messages')->updateOrInsert(
                    ['id' => $this->uid('message:'.$bookingKey.':'.$index)],
                    [
                        'conversation_id' => $conversationId,
                        'booking_id' => $referencedBookingKey !== null ? $bookingIds[$referencedBookingKey] : null,
                        'sender_id' => $senderType === 'user' ? $userId : $managerId,
                        'sender_type' => $senderType,
                        'message_type' => $referencedBookingKey !== null ? 'booking_reference' : 'text',
                        'content' => $content,
                        'created_at' => $sentAt,
                        'updated_at' => $sentAt,
                    ]
                );
            }

            DB::table('booking_threads')->updateOrInsert(
                ['booking_id' => $bookingIds[$bookingKey]],
                [
                    'conversation_id' => $conversationId,
                    'is_active' => $isActive,
                    'archived_at' => $isActive ? null : now()->subDays(18),
                    'created_at' => now()->addDays($messages[0][1]),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
