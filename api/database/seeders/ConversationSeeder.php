<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConversationSeeder extends Seeder
{
    public function run(): void
    {
        $userId = DB::table('users')->where('email', 'user@orus.com')->value('id');
        $managerId = DB::table('users')->where('email', 'manager@orus.com')->value('id');
        $establishments = DB::table('establishments')->limit(2)->get();

        $establishment1Id = $establishments[0]->id ?? null;
        $establishment2Id = $establishments[1]->id ?? null;

        $bookings = DB::table('bookings')->get();
        $booking1Id = $bookings[0]->id ?? null; // Completed - Rex
        $booking2Id = $bookings[1]->id ?? null; // Confirmed - Rex + Minou
        $booking5Id = $bookings[4]->id ?? null; // Cancelled - Rex

        if (! $establishment1Id || ! $userId) {
            throw new \RuntimeException('Required data missing. Ensure UsersSeeder and EstablishmentSeeder ran successfully.');
        }

        // === CONVERSATION 1 ===
        $existingConv1 = DB::table('conversations')
            ->where('user_id', $userId)
            ->where('establishment_id', $establishment1Id)
            ->first();

        if ($existingConv1) {
            $conversation1Id = $existingConv1->id;
            DB::table('conversations')->where('id', $conversation1Id)->update([
                'last_message_at' => Carbon::now()->subHours(2),
                'updated_at' => Carbon::now()->subHours(2),
            ]);
        } else {
            $conversation1Id = (string) Str::uuid();
            DB::table('conversations')->insert([
                'id' => $conversation1Id,
                'user_id' => $userId,
                'establishment_id' => $establishment1Id,
                'last_message_at' => Carbon::now()->subHours(2),
                'created_at' => Carbon::now()->subDays(30),
                'updated_at' => Carbon::now()->subHours(2),
            ]);
        }

        // Initial contact
        $message1Id = (string) Str::uuid();
        DB::table('messages')->insert([
            'id' => $message1Id,
            'conversation_id' => $conversation1Id,
            'booking_id' => null,
            'sender_id' => $userId,
            'sender_type' => 'user',
            'message_type' => 'text',
            'content' => 'Bonjour, j\'aimerais savoir si vous acceptez les chiens de grande taille ?',
            'created_at' => Carbon::now()->subDays(30),
            'updated_at' => Carbon::now()->subDays(30),
        ]);

        $message2Id = (string) Str::uuid();
        DB::table('messages')->insert([
            'id' => $message2Id,
            'conversation_id' => $conversation1Id,
            'booking_id' => null,
            'sender_id' => $managerId,
            'sender_type' => 'establishment',
            'message_type' => 'text',
            'content' => 'Bonjour ! Oui, nous acceptons les chiens de toutes tailles. Nous avons de l\'expérience avec les grandes races. N\'hésitez pas à me parler de votre chien !',
            'created_at' => Carbon::now()->subDays(30)->addHours(1),
            'updated_at' => Carbon::now()->subDays(30)->addHours(1),
        ]);

        // === Booking 1 (Completed - Rex) ===
        if ($booking1Id) {
            // User sends booking request
            DB::table('messages')->insert([
                'id' => (string) Str::uuid(),
                'conversation_id' => $conversation1Id,
                'booking_id' => $booking1Id,
                'sender_id' => $userId,
                'sender_type' => 'user',
                'message_type' => 'booking_reference',
                'content' => 'Je souhaite réserver pour Rex.',
                'created_at' => Carbon::now()->subDays(26),
                'updated_at' => Carbon::now()->subDays(26),
            ]);

            // Establishment confirms
            DB::table('messages')->insert([
                'id' => (string) Str::uuid(),
                'conversation_id' => $conversation1Id,
                'booking_id' => $booking1Id,
                'sender_id' => $managerId,
                'sender_type' => 'establishment',
                'message_type' => 'booking_reference',
                'content' => 'Votre réservation pour Rex est confirmée !',
                'created_at' => Carbon::now()->subDays(25),
                'updated_at' => Carbon::now()->subDays(25),
            ]);

            $existingThread1 = DB::table('booking_threads')->where('booking_id', $booking1Id)->first();
            if (! $existingThread1) {
                DB::table('booking_threads')->insert([
                    'conversation_id' => $conversation1Id,
                    'booking_id' => $booking1Id,
                    'is_active' => false,
                    'archived_at' => Carbon::now()->subDays(14),
                    'created_at' => Carbon::now()->subDays(25),
                    'updated_at' => Carbon::now()->subDays(14),
                ]);
            }

            // DB::table('messages')->insert([
            //     'id' => (string) Str::uuid(),
            //     'conversation_id' => $conversation1Id,
            //     'booking_id' => $booking1Id,
            //     'sender_id' => null,
            //     'sender_type' => 'system',
            //     'message_type' => 'system',
            //     'content' => 'Thread de réservation créé - Check-in: ' . Carbon::now()->subDays(20)->format('d/m/Y') . ', Check-out: ' . Carbon::now()->subDays(15)->format('d/m/Y'),
            //     'created_at' => Carbon::now()->subDays(25),
            //     'updated_at' => Carbon::now()->subDays(25),
            // ]);

            $message5Id = (string) Str::uuid();
            DB::table('messages')->insert([
                'id' => $message5Id,
                'conversation_id' => $conversation1Id,
                'booking_id' => $booking1Id,
                'sender_id' => $userId,
                'sender_type' => 'user',
                'message_type' => 'text',
                'content' => 'Bonjour, je vous envoie le carnet de vaccination de Rex.',
                'created_at' => Carbon::now()->subDays(22),
                'updated_at' => Carbon::now()->subDays(22),
            ]);

            DB::table('message_files')->insert([
                'id' => (string) Str::uuid(),
                'message_id' => $message5Id,
                'file_name' => 'carnet_vaccination_rex.pdf',
                'file_path' => 'messages/files/carnet_vaccination_rex.pdf',
                'file_type' => 'document',
                'file_size' => 524288,
                'mime_type' => 'application/pdf',
                'created_at' => Carbon::now()->subDays(22),
            ]);

            DB::table('messages')->insert([
                'id' => (string) Str::uuid(),
                'conversation_id' => $conversation1Id,
                'booking_id' => $booking1Id,
                'sender_id' => $managerId,
                'sender_type' => 'establishment',
                'message_type' => 'text',
                'content' => 'Parfait, tout est en ordre ! Rex sera entre de bonnes mains.',
                'created_at' => Carbon::now()->subDays(22)->addHours(2),
                'updated_at' => Carbon::now()->subDays(22)->addHours(2),
            ]);

            $message7Id = (string) Str::uuid();
            DB::table('messages')->insert([
                'id' => $message7Id,
                'conversation_id' => $conversation1Id,
                'booking_id' => $booking1Id,
                'sender_id' => $managerId,
                'sender_type' => 'establishment',
                'message_type' => 'file',
                'content' => 'Rex s\'amuse bien au parc !',
                'created_at' => Carbon::now()->subDays(18),
                'updated_at' => Carbon::now()->subDays(18),
            ]);

            DB::table('message_files')->insert([
                'id' => (string) Str::uuid(),
                'message_id' => $message7Id,
                'file_name' => 'rex_parc.jpg',
                'file_path' => 'messages/photos/rex_parc.jpg',
                'file_type' => 'image',
                'file_size' => 2097152,
                'mime_type' => 'image/jpeg',
                'created_at' => Carbon::now()->subDays(18),
            ]);

            DB::table('messages')->insert([
                'id' => (string) Str::uuid(),
                'conversation_id' => $conversation1Id,
                'booking_id' => $booking1Id,
                'sender_id' => $userId,
                'sender_type' => 'user',
                'message_type' => 'text',
                'content' => 'Merci beaucoup ! Il a l\'air très heureux !',
                'created_at' => Carbon::now()->subDays(18)->addHours(1),
                'updated_at' => Carbon::now()->subDays(18)->addHours(1),
            ]);
        }

        // === Booking 5 (Cancelled - Rex) — demonstrates refused state ===
        if ($booking5Id) {
            // User sends booking request
            DB::table('messages')->insert([
                'id' => (string) Str::uuid(),
                'conversation_id' => $conversation1Id,
                'booking_id' => $booking5Id,
                'sender_id' => $userId,
                'sender_type' => 'user',
                'message_type' => 'booking_reference',
                'content' => 'Je voudrais réserver Rex pour quelques jours.',
                'created_at' => Carbon::now()->subDays(7),
                'updated_at' => Carbon::now()->subDays(7),
            ]);

            // Establishment refuses
            DB::table('messages')->insert([
                'id' => (string) Str::uuid(),
                'conversation_id' => $conversation1Id,
                'booking_id' => $booking5Id,
                'sender_id' => $managerId,
                'sender_type' => 'establishment',
                'message_type' => 'booking_reference',
                'content' => 'Désolé, nous ne pouvons pas honorer cette réservation sur ces dates, nous sommes complets.',
                'created_at' => Carbon::now()->subDays(6),
                'updated_at' => Carbon::now()->subDays(6),
            ]);

            $existingThread5 = DB::table('booking_threads')->where('booking_id', $booking5Id)->first();
            if (! $existingThread5) {
                DB::table('booking_threads')->insert([
                    'conversation_id' => $conversation1Id,
                    'booking_id' => $booking5Id,
                    'is_active' => false,
                    'archived_at' => Carbon::now()->subDays(2),
                    'created_at' => Carbon::now()->subDays(7),
                    'updated_at' => Carbon::now()->subDays(2),
                ]);
            }
        }

        // === Booking 2 (Confirmed - Rex + Minou) ===
        if ($booking2Id) {
            // User sends booking request
            DB::table('messages')->insert([
                'id' => (string) Str::uuid(),
                'conversation_id' => $conversation1Id,
                'booking_id' => $booking2Id,
                'sender_id' => $userId,
                'sender_type' => 'user',
                'message_type' => 'booking_reference',
                'content' => 'Je souhaite réserver pour Rex et Minou cette fois !',
                'created_at' => Carbon::now()->subDays(4),
                'updated_at' => Carbon::now()->subDays(4),
            ]);

            // Establishment confirms
            DB::table('messages')->insert([
                'id' => (string) Str::uuid(),
                'conversation_id' => $conversation1Id,
                'booking_id' => $booking2Id,
                'sender_id' => $managerId,
                'sender_type' => 'establishment',
                'message_type' => 'booking_reference',
                'content' => 'C\'est avec plaisir ! La réservation est confirmée pour Rex et Minou.',
                'created_at' => Carbon::now()->subDays(3),
                'updated_at' => Carbon::now()->subDays(3),
            ]);

            $existingThread2 = DB::table('booking_threads')->where('booking_id', $booking2Id)->first();
            if (! $existingThread2) {
                DB::table('booking_threads')->insert([
                    'conversation_id' => $conversation1Id,
                    'booking_id' => $booking2Id,
                    'is_active' => true,
                    'archived_at' => null,
                    'created_at' => Carbon::now()->subDays(3),
                    'updated_at' => Carbon::now()->subDays(3),
                ]);
            }

            // DB::table('messages')->insert([
            //     'id' => (string) Str::uuid(),
            //     'conversation_id' => $conversation1Id,
            //     'booking_id' => $booking2Id,
            //     'sender_id' => null,
            //     'sender_type' => 'system',
            //     'message_type' => 'system',
            //     'content' => 'Thread de réservation créé - Check-in: '.Carbon::now()->addDays(5)->format('d/m/Y').', Check-out: '.Carbon::now()->addDays(12)->format('d/m/Y'),
            //     'created_at' => Carbon::now()->subDays(3),
            //     'updated_at' => Carbon::now()->subDays(3),
            // ]);

            DB::table('messages')->insert([
                'id' => (string) Str::uuid(),
                'conversation_id' => $conversation1Id,
                'booking_id' => $booking2Id,
                'sender_id' => $userId,
                'sender_type' => 'user',
                'message_type' => 'text',
                'content' => 'Bonjour ! Petite précision : Minou n\'aime pas trop les autres chats. Est-ce que cela pose problème ?',
                'created_at' => Carbon::now()->subDays(2),
                'updated_at' => Carbon::now()->subDays(2),
            ]);

            DB::table('messages')->insert([
                'id' => (string) Str::uuid(),
                'conversation_id' => $conversation1Id,
                'booking_id' => $booking2Id,
                'sender_id' => $managerId,
                'sender_type' => 'establishment',
                'message_type' => 'text',
                'content' => 'Pas de souci ! Nous avons des espaces séparés et nous gérons cela régulièrement. Minou aura son propre espace tranquille.',
                'created_at' => Carbon::now()->subHours(2),
                'updated_at' => Carbon::now()->subHours(2),
            ]);
        }

        // General message (outside threads)
        DB::table('messages')->insert([
            'id' => (string) Str::uuid(),
            'conversation_id' => $conversation1Id,
            'booking_id' => null,
            'sender_id' => $userId,
            'sender_type' => 'user',
            'message_type' => 'text',
            'content' => 'Super ! J\'ai hâte. Merci pour votre professionnalisme.',
            'created_at' => Carbon::now()->subHours(1),
            'updated_at' => Carbon::now()->subHours(1),
        ]);

        // === CONVERSATION 2: Simple conversation without bookings ===
        if ($establishment2Id) {
            $existingConv2 = DB::table('conversations')
                ->where('user_id', $userId)
                ->where('establishment_id', $establishment2Id)
                ->first();

            if ($existingConv2) {
                $conversation2Id = $existingConv2->id;
            } else {
                $conversation2Id = (string) Str::uuid();
                DB::table('conversations')->insert([
                    'id' => $conversation2Id,
                    'user_id' => $userId,
                    'establishment_id' => $establishment2Id,
                    'last_message_at' => Carbon::now()->subDays(5),
                    'created_at' => Carbon::now()->subDays(10),
                    'updated_at' => Carbon::now()->subDays(5),
                ]);
            }

            DB::table('messages')->insert([
                'id' => (string) Str::uuid(),
                'conversation_id' => $conversation2Id,
                'booking_id' => null,
                'sender_id' => $userId,
                'sender_type' => 'user',
                'message_type' => 'text',
                'content' => 'Bonjour, acceptez-vous les oiseaux ? J\'ai une perruche.',
                'created_at' => Carbon::now()->subDays(10),
                'updated_at' => Carbon::now()->subDays(10),
            ]);

            DB::table('messages')->insert([
                'id' => (string) Str::uuid(),
                'conversation_id' => $conversation2Id,
                'booking_id' => null,
                'sender_id' => $managerId,
                'sender_type' => 'establishment',
                'message_type' => 'text',
                'content' => 'Bonjour ! Malheureusement, nous ne sommes pas équipés pour les oiseaux pour le moment. Désolé !',
                'created_at' => Carbon::now()->subDays(10)->addHours(3),
                'updated_at' => Carbon::now()->subDays(10)->addHours(3),
            ]);

            DB::table('messages')->insert([
                'id' => (string) Str::uuid(),
                'conversation_id' => $conversation2Id,
                'booking_id' => null,
                'sender_id' => $userId,
                'sender_type' => 'user',
                'message_type' => 'text',
                'content' => 'Pas de problème, merci de votre réponse !',
                'created_at' => Carbon::now()->subDays(5),
                'updated_at' => Carbon::now()->subDays(5),
            ]);
        }

        DB::table('message_reads')->insert([
            [
                'message_id' => $message1Id,
                'user_id' => $managerId,
                'read_at' => Carbon::now()->subDays(30)->addMinutes(30),
            ],
            [
                'message_id' => $message2Id,
                'user_id' => $userId,
                'read_at' => Carbon::now()->subDays(30)->addHours(2),
            ],
        ]);
    }
}
