<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Motorcycle;
use App\Models\User;
use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\ClientBuilder;
use RuntimeException;

class Neo4jGraphService
{
    private function client()
    {
        $uri = config('services.neo4j.uri');
        $username = config('services.neo4j.username');
        $password = config('services.neo4j.password');

        if (!$uri || !$username || !$password) {
            throw new RuntimeException('Konfigurasi Neo4j belum lengkap. Isi NEO4J_URI, NEO4J_USERNAME, dan NEO4J_PASSWORD di file .env.');
        }

        return ClientBuilder::create()
            ->withDriver('neo4j', $uri, Authenticate::basic($username, $password))
            ->withDefaultDriver('neo4j')
            ->build();
    }

    public function syncRentalGraph(): array
    {
        $client = $this->client();

        $client->run('CREATE CONSTRAINT user_id_unique IF NOT EXISTS FOR (u:User) REQUIRE u.id IS UNIQUE');
        $client->run('CREATE CONSTRAINT motorcycle_id_unique IF NOT EXISTS FOR (m:Motorcycle) REQUIRE m.id IS UNIQUE');

        $users = User::query()->get(['id', 'name', 'username', 'email']);
        foreach ($users as $user) {
            $client->run(
                'MERGE (u:User {id: $id}) SET u.name = $name, u.username = $username, u.email = $email',
                [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                ]
            );
        }

        $motorcycles = Motorcycle::query()->get(['id', 'brand', 'type', 'category', 'transmission', 'license_plate']);
        foreach ($motorcycles as $motorcycle) {
            $client->run(
                'MERGE (m:Motorcycle {id: $id}) SET m.brand = $brand, m.type = $type, m.category = $category, m.transmission = $transmission, m.license_plate = $licensePlate',
                [
                    'id' => $motorcycle->id,
                    'brand' => $motorcycle->brand,
                    'type' => $motorcycle->type,
                    'category' => $motorcycle->category,
                    'transmission' => $motorcycle->transmission,
                    'licensePlate' => $motorcycle->license_plate,
                ]
            );
        }

        // Hanya booking yang pembayarannya berhasil yang dijadikan dasar rekomendasi.
        $bookings = Booking::query()
            ->where('payment_status', 'paid')
            ->get(['id', 'user_id', 'motorcycle_id', 'created_at']);

        foreach ($bookings as $booking) {
            $client->run(
                'MATCH (u:User {id: $userId}), (m:Motorcycle {id: $motorcycleId}) MERGE (u)-[b:BOOKED {booking_id: $bookingId}]->(m) SET b.booked_at = $bookedAt',
                [
                    'userId' => $booking->user_id,
                    'motorcycleId' => $booking->motorcycle_id,
                    'bookingId' => $booking->id,
                    'bookedAt' => optional($booking->created_at)->toDateTimeString(),
                ]
            );
        }

        return [
            'users' => $users->count(),
            'motorcycles' => $motorcycles->count(),
            'paid_bookings' => $bookings->count(),
        ];
    }

    public function motorcycleRecommendations(int $userId, int $limit = 5): array
    {
        $result = $this->client()->run(
            'MATCH (user:User {id: $userId})-[:BOOKED]->(:Motorcycle)<-[:BOOKED]-(other:User)-[:BOOKED]->(recommended:Motorcycle) WHERE other.id <> user.id AND NOT (user)-[:BOOKED]->(recommended) RETURN DISTINCT recommended.id AS motorcycle_id, recommended.brand AS brand, recommended.type AS type, recommended.category AS category, recommended.license_plate AS license_plate, count(DISTINCT other) AS score ORDER BY score DESC, brand ASC LIMIT $limit',
            ['userId' => $userId, 'limit' => $limit]
        );

        $recommendations = [];
        foreach ($result as $record) {
            $recommendations[] = [
                'motorcycle_id' => $record->get('motorcycle_id'),
                'brand' => $record->get('brand'),
                'type' => $record->get('type'),
                'category' => $record->get('category'),
                'license_plate' => $record->get('license_plate'),
                'score' => $record->get('score'),
            ];
        }

        return $recommendations;
    }
}
