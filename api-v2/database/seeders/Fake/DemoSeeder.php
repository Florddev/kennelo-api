<?php

declare(strict_types=1);

namespace Database\Seeders\Fake;

use App\Enums\BookingStatusEnum;
use App\Enums\MessageTypeEnum;
use App\Enums\OrganizationRoleEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ServiceOfferEnum;
use App\Enums\WeekDayEnum;
use App\Models\Activity;
use App\Models\ActivityDocument;
use App\Models\ActivityUnitType;
use App\Models\Address;
use App\Models\AgendaResource;
use App\Models\AnimalType;
use App\Models\Booking;
use App\Models\BookingThread;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Pet;
use App\Models\Profession;
use App\Models\Review;
use App\Models\ReviewResponse;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Billing\InvoiceService;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Database\Factories\BookingFactory;
use Illuminate\Database\Seeder;

/**
 * Démonstration : une entreprise vérifiée avec une pension et un salon de toilettage réservables, son équipe, une
 * pet-sitter à Lyon, deux clients et leurs animaux, des réservations dans chaque état utile aux écrans (demande,
 * à venir, en cours, terminée et notée), une conversation et des factures.
 *
 * Comptes, tous avec le mot de passe « password » : admin@kennelo.test (back-office), pro@kennelo.test
 * (propriétaire de l'entreprise), lea@kennelo.test (toiletteuse), julie@kennelo.test (pet-sitter),
 * camille@kennelo.test et hugo@kennelo.test (clients).
 */
class DemoSeeder extends Seeder
{
    private const array ANNECY = ['line1' => '12 rue du Lac', 'postal_code' => '74000', 'city' => 'Annecy', 'latitude' => 45.8992, 'longitude' => 6.1294];

    private const array LYON = ['line1' => '8 rue Mercière', 'postal_code' => '69002', 'city' => 'Lyon', 'latitude' => 45.7640, 'longitude' => 4.8357];

    public function run(): void
    {
        if (User::query()->withInactive()->where('email', 'pro@kennelo.test')->exists()) {
            $this->command->warn('Données de démonstration déjà présentes : rien à ajouter.');

            return;
        }

        $dog = AnimalType::query()->where('code', 'dog')->firstOrFail();
        $cat = AnimalType::query()->where('code', 'cat')->firstOrFail();

        User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Admin', 'email' => 'admin@kennelo.test'])->assignRole('admin');

        // L'entreprise, sa pension et son salon.
        $owner = User::factory()->create(['first_name' => 'Marc', 'last_name' => 'Rolland', 'email' => 'pro@kennelo.test']);
        $organization = Organization::factory()->verified()->withStripe()->withBillingMandate()->create([
            'owner_id' => $owner->id,
            'legal_name' => 'Les Pattes du Lac',
        ]);

        $pension = $this->activity($organization, 'boarding', 'Pension Les Pattes du Lac', [$dog, $cat], self::ANNECY);
        $box = ActivityUnitType::factory()->for($pension)->forSpecies($dog)->priced('28.00', '18.00')
            ->create(['name' => 'Box intérieur', 'quantity' => 6, 'max_animals_per_unit' => 2]);
        $catRoom = ActivityUnitType::factory()->for($pension)->forSpecies($cat)->priced('16.00')
            ->create(['name' => 'Chatterie', 'quantity' => 4, 'max_animals_per_unit' => 1]);

        $salon = $this->activity($organization, 'grooming', 'Salon Les Pattes du Lac', [$dog, $cat], [...self::ANNECY, 'line1' => '3 avenue de Genève']);
        $grooming = Service::factory()->for($organization)->create(['name' => 'Toilettage complet', 'requires_scheduling' => true]);
        ServicePrice::factory()->for($grooming)->create(['animal_type_id' => $dog->id, 'price' => '60.00', 'duration_minutes' => 60]);
        ServicePrice::factory()->for($grooming)->create(['animal_type_id' => $cat->id, 'price' => '45.00', 'duration_minutes' => 45]);
        $salon->services()->attach($grooming->id, [
            'organization_id' => $organization->id,
            'offered_as' => ServiceOfferEnum::STANDALONE,
            'adjustment_percent' => '0',
            'is_included' => false,
            'is_active' => true,
        ]);

        // Léa, toiletteuse : employée du salon, et sa ressource dans l'agenda.
        $lea = User::factory()->create(['first_name' => 'Léa', 'last_name' => 'Garnier', 'email' => 'lea@kennelo.test']);
        $member = OrganizationMember::factory()->for($organization)->withRole(OrganizationRoleEnum::EMPLOYEE, $salon->id)->create(['user_id' => $lea->id]);
        $groomer = AgendaResource::factory()->staff($member)->scheduledIn($salon)->create(['name' => 'Léa']);

        // Une pet-sitter à Lyon, pour une recherche qui ne renvoie pas qu'une entreprise.
        $julie = User::factory()->create(['first_name' => 'Julie', 'last_name' => 'Martin', 'email' => 'julie@kennelo.test']);
        $sitter = Organization::factory()->individual()->verified()->withStripe()->withBillingMandate()->create([
            'owner_id' => $julie->id,
            'legal_name' => 'Julie Martin',
        ]);
        $home = $this->activity($sitter, 'boarding', 'Chez Julie', [$dog], self::LYON);
        ActivityUnitType::factory()->for($home)->forSpecies($dog)->priced('22.00')->create(['name' => 'Chambre', 'quantity' => 2, 'max_animals_per_unit' => 1]);

        // Les clients et leurs animaux.
        $camille = User::factory()->create(['first_name' => 'Camille', 'last_name' => 'Durand', 'email' => 'camille@kennelo.test']);
        UserAddress::factory()->for($camille)->create([
            'label' => 'Maison',
            'is_default' => true,
            'address_id' => Address::factory()->create([...self::ANNECY, 'line1' => '5 chemin des Crêts', 'line2' => null, 'region' => 'Auvergne-Rhône-Alpes', 'country' => 'FR'])->id,
        ]);
        $rex = Pet::factory()->for($camille)->create([
            'animal_type_id' => $dog->id,
            'name' => 'Rex',
            'sex' => 'male',
            'birth_date' => '2021-04-12',
            'weight' => 28,
            'has_microchip' => true,
            'microchip_number' => '250269606012345',
        ]);
        $mina = Pet::factory()->for($camille)->create(['animal_type_id' => $cat->id, 'name' => 'Mina', 'sex' => 'female', 'birth_date' => '2022-09-03', 'weight' => 4]);

        $hugo = User::factory()->create(['first_name' => 'Hugo', 'last_name' => 'Petit', 'email' => 'hugo@kennelo.test']);
        $oslo = Pet::factory()->for($hugo)->create(['animal_type_id' => $dog->id, 'name' => 'Oslo', 'sex' => 'male', 'birth_date' => '2023-01-20', 'weight' => 12]);

        // Les réservations : terminée et notée, en cours, à venir, demande à accepter, rendez-vous.
        $today = CarbonImmutable::today();
        $past = $this->stay($camille, $box, '28.00', [$rex], $today->subDays(40), 5, BookingStatusEnum::COMPLETED);
        $current = $this->stay($camille, $catRoom, '16.00', [$mina], $today->subDays(2), 6, BookingStatusEnum::IN_PROGRESS);
        $upcoming = $this->stay($camille, $box, '28.00', [$rex], $today->addDays(21), 7, BookingStatusEnum::CONFIRMED);
        $this->stay($hugo, $box, '28.00', [$oslo], $today->addDays(10), 3, BookingStatusEnum::PENDING);

        $startsAt = CarbonImmutable::parse('next tuesday 10:00', $salon->timezone);
        $appointment = $this->booking(
            Booking::factory()->appointment(['activity' => $salon, 'service' => $grooming, 'resource' => $groomer], $startsAt->toIso8601String(), 60),
            $camille,
            $salon,
            '60.00',
            BookingStatusEnum::CONFIRMED,
        );
        $appointment->pets()->attach($rex->id);

        // Chaque paiement encaissé a ses factures, comme après une vraie capture.
        foreach ([$past, $current, $upcoming, $appointment] as $booking) {
            app(InvoiceService::class)->invoicePayment($booking->payments()->sole());
        }

        // Le séjour terminé est noté des deux côtés, et l'entreprise a répondu.
        $review = Review::factory()->for($past)->published()->rating('5.0')->create([
            'comment' => 'Rex est revenu ravi, et nous avons eu des photos chaque jour. Merci à toute l\'équipe !',
        ]);
        ReviewResponse::query()->create(['review_id' => $review->id, 'responder_id' => $owner->id, 'response' => 'Merci Camille, à très bientôt pour Rex !']);
        Review::factory()->for($past)->published()->aboutClient($owner->id)->rating('5.0')->create([
            'comment' => 'Cliente ponctuelle, carnet de santé à jour. Rex est un amour.',
        ]);

        // Une conversation au sujet du prochain séjour.
        $conversation = Conversation::factory()->for($camille)->for($pension)->create();
        BookingThread::query()->create(['booking_id' => $upcoming->id, 'conversation_id' => $conversation->id]);
        Message::factory()->for($conversation)->create([
            'booking_id' => $upcoming->id,
            'message_type' => MessageTypeEnum::BOOKING_REFERENCE,
            'content' => 'Bonjour ! Rex peut-il venir avec son panier ?',
            'created_at' => now()->subDays(3),
        ]);
        Message::factory()->for($conversation)->fromTeam($owner->id)->create([
            'content' => 'Bonjour Camille, bien sûr : apportez aussi ses croquettes habituelles.',
            'created_at' => now()->subDays(3)->addHour(),
        ]);
        Message::factory()->for($conversation)->create(['content' => 'Parfait, merci !', 'created_at' => now()->subDays(2)]);

        $camille->favoriteActivities()->attach($salon->id, ['created_at' => now()]);
    }

    /**
     * Activité validée et réservable, avec les justificatifs obligatoires de son métier et ouverte du lundi au
     * samedi de 9 h à 18 h.
     *
     * @param  list<AnimalType>  $species
     * @param  array<string, mixed>  $address
     */
    private function activity(Organization $organization, string $professionCode, string $name, array $species, array $address): Activity
    {
        $profession = Profession::query()->where('code', $professionCode)->with('documentRequirements')->firstOrFail();

        $activity = Activity::factory()->approved()->for($organization)->for($profession)->forSpecies(...$species)->create([
            'name' => $name,
            'description' => "{$name} : un accueil attentif, en petit comité.",
            'address_id' => Address::factory()->create([...$address, 'line2' => null, 'region' => 'Auvergne-Rhône-Alpes', 'country' => 'FR'])->id,
        ]);

        // Sans eux, l'activité n'apparaît pas dans la recherche.
        foreach ($profession->documentRequirements->where('is_required', true) as $requirement) {
            ActivityDocument::factory()->approved()->for($activity)->create([
                'document_type' => $requirement->document_type,
                'expires_at' => $requirement->validity_months === null ? null : now()->addMonths(10)->toDateString(),
            ]);
        }

        $activity->openingHours()->createMany(array_map(
            fn (WeekDayEnum $day): array => ['weekday' => $day, 'opens_at' => '09:00', 'closes_at' => '18:00'],
            array_filter(WeekDayEnum::cases(), fn (WeekDayEnum $day): bool => $day !== WeekDayEnum::SUNDAY),
        ));

        // Relue pour ses valeurs par défaut en base (politique d'annulation).
        return $activity->refresh();
    }

    /**
     * Séjour d'une place par animal, au prix de base de la place.
     *
     * @param  list<Pet>  $pets
     */
    private function stay(User $client, ActivityUnitType $unitType, string $nightlyPrice, array $pets, CarbonImmutable $start, int $nights, BookingStatusEnum $status): Booking
    {
        $subtotal = Money::multiply($nightlyPrice, (string) $nights);
        $booking = $this->booking(
            Booking::factory()->between($start->toDateString(), $start->addDays($nights)->toDateString()),
            $client,
            $unitType->activity()->firstOrFail(),
            Money::multiply($subtotal, (string) count($pets)),
            $status,
        );

        foreach ($pets as $pet) {
            $unit = $booking->units()->create([
                'activity_unit_type_id' => $unitType->id,
                'quantity' => 1,
                'nights' => $nights,
                'subtotal' => $subtotal,
                'price_breakdown' => [],
            ]);
            $booking->pets()->attach($pet->id, ['booking_unit_id' => $unit->id]);
        }

        return $booking;
    }

    /**
     * Réservation du client, frais Kennelo et commission calculés comme au devis. Hors demande en attente, le
     * paiement est encaissé.
     */
    private function booking(BookingFactory $factory, User $client, Activity $activity, string $itemsAmount, BookingStatusEnum $status): Booking
    {
        $organization = $activity->organization()->firstOrFail();
        $serviceFee = Money::multiply($itemsAmount, (string) setting('user_service_fee_rate'));
        $platformFee = Money::multiply($itemsAmount, $organization->effectivePlan()->commissionRate());

        return $factory->for($client)->create([
            'activity_id' => $activity->id,
            'status' => $status,
            'payment_status' => $status === BookingStatusEnum::PENDING ? PaymentStatusEnum::REQUIRES_CAPTURE : PaymentStatusEnum::SUCCEEDED,
            'total_price' => Money::sum($itemsAmount, $serviceFee),
            'service_fee' => $serviceFee,
            'platform_fee' => $platformFee,
            'activity_amount' => bcsub($itemsAmount, $platformFee, 2),
            'cancellation_policy' => $activity->cancellation_policy,
        ]);
    }
}
