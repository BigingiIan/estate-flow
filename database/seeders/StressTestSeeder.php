<?php

namespace Database\Seeders;

use App\Models\Lease;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StressTestSeeder extends Seeder
{
    protected array $residentialPrefixes = [
        'Acacia', 'Baobab', 'Cedar', 'Golden', 'Jasmine', 'Karibu', 'Milele', 'Olive',
        'Palm', 'Savannah', 'Sunset', 'Tulip', 'Umoja', 'Willow', 'Zuri',
    ];

    protected array $commercialPrefixes = [
        'Atlas', 'Centenary', 'Commerce', 'Gateway', 'Horizon', 'Impact', 'Keystone',
        'Landmark', 'Meridian', 'Prime', 'Pinnacle', 'Summit', 'Trade', 'Vertex',
    ];

    protected array $residentialSuffixes = ['Residences', 'Apartments', 'Gardens', 'Court', 'Heights', 'Villas'];
    protected array $commercialSuffixes = ['Plaza', 'Centre', 'Square', 'Tower', 'Business Park', 'Exchange'];
    protected array $locations = [
        'Westlands, Nairobi', 'Kilimani, Nairobi', 'Lavington, Nairobi', 'Kileleshwa, Nairobi',
        'Riverside, Nairobi', 'Upper Hill, Nairobi', 'Parklands, Nairobi', 'South B, Nairobi',
        'South C, Nairobi', 'Karen, Nairobi', 'Ngong Road, Nairobi', 'Mombasa Road, Nairobi',
        'Industrial Area, Nairobi', 'Syokimau, Machakos', 'Ruaka, Kiambu', 'Ruiru, Kiambu',
    ];

    public function run(): void
    {
        $primaryLandlord = User::where('email', 'ian@estateflow.co.ke')->first()
            ?? User::factory()->create([
                'name' => 'Ian Bigingi',
                'email' => 'ian@estateflow.co.ke',
                'phone' => '+254700000001',
                'role' => 'landlord',
            ]);

        $otherLandlords = collect([
            ['name' => 'Mercy Wanjiru', 'email' => 'mercy@estateflow.co.ke', 'phone' => '+254700000101'],
            ['name' => 'Dennis Okoth', 'email' => 'dennis@estateflow.co.ke', 'phone' => '+254700000102'],
        ])->map(fn (array $user) => User::firstOrCreate(
            ['email' => $user['email']],
            $user + ['role' => 'landlord', 'password' => bcrypt('password'), 'email_verified_at' => now()]
        ));

        $this->seedPortfolio($primaryLandlord, 18, [24, 52]);

        foreach ($otherLandlords as $landlord) {
            $this->seedPortfolio($landlord, 5, [10, 24]);
        }
    }

    protected function seedPortfolio(User $user, int $propertyCount, array $unitRange): void
    {
        for ($propertyIndex = 1; $propertyIndex <= $propertyCount; $propertyIndex++) {
            $propertyType = $this->weightedPick([
                'residential' => 55,
                'commercial' => 25,
                'mixed' => 20,
            ]);

            $property = Property::create([
                'user_id' => $user->id,
                'name' => $this->makePropertyName($propertyType, $propertyIndex),
                'type' => $propertyType,
                'location' => fake()->randomElement($this->locations),
                'description' => $this->makePropertyDescription($propertyType),
            ]);

            $unitCount = random_int($unitRange[0], $unitRange[1]);

            for ($unitNumber = 1; $unitNumber <= $unitCount; $unitNumber++) {
                $status = $this->weightedPick([
                    'occupied' => 68,
                    'vacant' => 22,
                    'maintenance' => 10,
                ]);

                $unitType = $this->pickUnitType($propertyType);
                [$bedrooms, $bathrooms, $sizeSqft, $ratePerSqft, $baseRent, $furnished] = $this->makeUnitMetrics($unitType, $propertyType);

                $unit = Unit::create([
                    'property_id' => $property->id,
                    'unit_number' => $this->makeUnitNumber($propertyType, $unitNumber),
                    'unit_type' => $unitType,
                    'base_rent' => $baseRent,
                    'status' => $status,
                    'bedrooms' => $bedrooms,
                    'bathrooms' => $bathrooms,
                    'size_sqft' => $sizeSqft,
                    'rate_per_sqft' => $ratePerSqft,
                    'floor' => $this->makeFloorLabel($unitType),
                    'is_furnished' => $furnished,
                    'service_charge' => (string) random_int(1500, 12000),
                ]);

                if ($status === 'occupied') {
                    if (random_int(1, 100) <= 35) {
                        $historicalLease = $this->createLeaseForUnit($unit, 'historical');
                        $this->seedLeaseTransactions($historicalLease, false);
                    }

                    $activeLease = $this->createLeaseForUnit($unit, 'active');
                    $this->seedLeaseTransactions($activeLease, true);
                    continue;
                }

                if (random_int(1, 100) <= 60) {
                    $historicalLease = $this->createLeaseForUnit($unit, 'historical');
                    $this->seedLeaseTransactions($historicalLease, false);
                }
            }
        }
    }

    protected function createLeaseForUnit(Unit $unit, string $mode): Lease
    {
        $isCommercial = in_array($unit->unit_type, ['office', 'retail', 'warehouse'], true);
        $startDate = Carbon::now()->subMonths(random_int($mode === 'active' ? 1 : 6, $mode === 'active' ? 24 : 36))
            ->startOfMonth()
            ->addDays(random_int(0, 10));

        $endDate = match ($mode) {
            'active' => random_int(1, 100) <= 40
                ? null
                : $startDate->copy()->addMonths(max(random_int(12, 36), $startDate->diffInMonths(Carbon::now()) + random_int(3, 18)))->subDay(),
            default => (clone $startDate)->copy()->addMonths(random_int(6, 24))->subDay(),
        };

        if ($mode !== 'active' && $endDate->isFuture()) {
            $endDate = Carbon::now()->subMonths(random_int(1, 8))->endOfMonth();
        }

        $tenant = $this->makeTenant($isCommercial);
        $depositAmount = random_int(60, 140) / 100 * (float) $unit->base_rent;
        $serviceCharge = (float) preg_replace('/[^0-9.]/', '', (string) $unit->service_charge);
        $depositStatus = $mode === 'historical' && random_int(1, 100) <= 25 ? 'refunded' : $this->weightedPick([
            'paid' => 78,
            'pending' => 22,
        ]);

        return Lease::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate?->toDateString(),
            'rent_amount' => $unit->base_rent,
            'deposit_amount' => round($depositAmount, 2),
            'deposit_status' => $depositStatus,
            'status' => $mode === 'active'
                ? 'active'
                : fake()->randomElement(['terminated', 'expired']),
            'notes' => fake()->optional(0.35)->sentence(),
            'service_charge' => $serviceCharge,
            'escalation_rate' => fake()->randomElement([0, 0, 5, 7.5, 10]),
            'lease_type' => $isCommercial
                ? fake()->randomElement(['standard', 'periodic', 'short_term'])
                : fake()->randomElement(['standard', 'standard', 'periodic']),
            'business_name' => $isCommercial ? $this->makeBusinessName() : null,
        ]);
    }

    protected function seedLeaseTransactions(Lease $lease, bool $isActive): void
    {
        $start = Carbon::parse($lease->start_date)->startOfMonth();
        $finalMonth = $isActive
            ? Carbon::now()->startOfMonth()
            : Carbon::parse($lease->end_date ?? $lease->start_date)->startOfMonth();

        if ($lease->deposit_status !== 'pending') {
            Transaction::create([
                'lease_id' => $lease->id,
                'type' => 'deposit',
                'amount' => (float) $lease->deposit_amount,
                'reference_code' => $this->makeReference(),
                'payment_method' => fake()->randomElement(['bank_transfer', 'mpesa', 'cash']),
                'paid_at' => Carbon::parse($lease->start_date)->addDays(random_int(0, 4)),
                'notes' => 'Security deposit payment',
            ]);
        }

        $months = max(1, $start->diffInMonths($finalMonth) + 1);
        $recentMissMonths = $isActive ? random_int(0, min(3, $months)) : 0;
        $recentMissed = collect();

        if ($recentMissMonths > 0) {
            for ($i = 0; $i < $recentMissMonths; $i++) {
                $recentMissed->push(Carbon::now()->startOfMonth()->subMonths($i)->format('Y-m'));
            }
        }

        for ($cursor = $start->copy(); $cursor->lte($finalMonth); $cursor->addMonth()) {
            $monthKey = $cursor->format('Y-m');
            $skipMonth = $recentMissed->contains($monthKey) || random_int(1, 100) <= ($isActive ? 9 : 4);

            if (!$skipMonth) {
                $paidFull = random_int(1, 100) > 18;
                $amount = $paidFull
                    ? (float) $lease->rent_amount
                    : round((float) $lease->rent_amount * (random_int(45, 85) / 100), 2);

                Transaction::create([
                    'lease_id' => $lease->id,
                    'type' => 'rent',
                    'amount' => $amount,
                    'reference_code' => $this->makeReference(),
                    'payment_method' => fake()->randomElement(['mpesa', 'bank_transfer', 'cash', 'cheque']),
                    'paid_at' => $cursor->copy()->addDays(random_int(0, 26)),
                    'notes' => $paidFull ? 'Monthly rent payment' : 'Partial rent payment',
                ]);

                if (!$paidFull && random_int(1, 100) <= 45) {
                    Transaction::create([
                        'lease_id' => $lease->id,
                        'type' => 'penalty',
                        'amount' => round((float) $lease->rent_amount * (random_int(3, 12) / 100), 2),
                        'reference_code' => $this->makeReference(),
                        'payment_method' => fake()->randomElement(['mpesa', 'cash']),
                        'paid_at' => $cursor->copy()->addDays(random_int(18, 28)),
                        'notes' => 'Late payment penalty',
                    ]);
                }
            }
        }

        if ($lease->deposit_status === 'refunded' && !$isActive) {
            Transaction::create([
                'lease_id' => $lease->id,
                'type' => 'refund',
                'amount' => round((float) $lease->deposit_amount * (random_int(60, 100) / 100), 2),
                'reference_code' => $this->makeReference(),
                'payment_method' => fake()->randomElement(['bank_transfer', 'mpesa']),
                'paid_at' => Carbon::parse($lease->end_date ?? $lease->start_date)->addDays(random_int(2, 14)),
                'notes' => 'Deposit refund after checkout',
            ]);
        }
    }

    protected function makeTenant(bool $isCommercial): Tenant
    {
        $fullName = fake()->name();

        return Tenant::create([
            'full_name' => $fullName,
            'email' => fake()->optional(0.85)->safeEmail(),
            'phone' => '+2547' . fake()->numerify('########'),
            'id_type' => fake()->randomElement(['national_id', 'passport', 'company_registration']),
            'id_number' => strtoupper(Str::random(2)) . fake()->unique()->numerify('######'),
            'emergency_contact' => fake()->optional(0.7)->name(),
            'emergency_contact_phone' => fake()->optional(0.65)->passthrough('+2547' . fake()->numerify('########')),
        ]);
    }

    protected function makePropertyName(string $type, int $index): string
    {
        if ($type === 'commercial') {
            return fake()->randomElement($this->commercialPrefixes) . ' ' . fake()->randomElement($this->commercialSuffixes);
        }

        if ($type === 'mixed') {
            return fake()->randomElement(array_merge($this->residentialPrefixes, $this->commercialPrefixes)) . ' Hub ' . $index;
        }

        return fake()->randomElement($this->residentialPrefixes) . ' ' . fake()->randomElement($this->residentialSuffixes);
    }

    protected function makePropertyDescription(string $type): string
    {
        return match ($type) {
            'commercial' => fake()->sentence() . ' Flexible office, retail, and light industrial spaces.',
            'mixed' => fake()->sentence() . ' Balanced mix of residential units and income-generating commercial space.',
            default => fake()->sentence() . ' Residential rental portfolio with mixed unit sizes and occupancy patterns.',
        };
    }

    protected function pickUnitType(string $propertyType): string
    {
        return match ($propertyType) {
            'commercial' => $this->weightedPick(['office' => 45, 'retail' => 30, 'warehouse' => 15, 'studio' => 10]),
            'mixed' => $this->weightedPick(['apartment' => 50, 'office' => 20, 'retail' => 15, 'studio' => 10, 'warehouse' => 5]),
            default => $this->weightedPick(['apartment' => 70, 'studio' => 30]),
        };
    }

    protected function makeUnitMetrics(string $unitType, string $propertyType): array
    {
        return match ($unitType) {
            'warehouse' => [0, 1, random_int(900, 3200), random_int(55, 110), random_int(85000, 260000), false],
            'office' => [0, random_int(1, 3), random_int(250, 1200), random_int(110, 220), random_int(45000, 180000), false],
            'retail' => [0, random_int(1, 2), random_int(180, 900), random_int(130, 260), random_int(60000, 220000), false],
            'studio' => [1, 1, random_int(280, 520), random_int(85, 130), random_int(18000, 42000), random_int(1, 100) <= 25],
            default => [
                $propertyType === 'residential' ? random_int(1, 4) : random_int(1, 3),
                random_int(1, 3),
                random_int(450, 1850),
                random_int(70, 150),
                random_int(25000, 145000),
                random_int(1, 100) <= 30,
            ],
        };
    }

    protected function makeUnitNumber(string $propertyType, int $sequence): string
    {
        $prefix = match ($propertyType) {
            'commercial' => fake()->randomElement(['S', 'O', 'W', 'T']),
            'mixed' => fake()->randomElement(['M', 'R', 'C']),
            default => fake()->randomElement(['A', 'B', 'C', 'D']),
        };

        return $prefix . str_pad((string) $sequence, random_int(2, 3), '0', STR_PAD_LEFT);
    }

    protected function makeFloorLabel(string $unitType): ?string
    {
        if ($unitType === 'warehouse') {
            return 'Ground';
        }

        $floor = random_int(0, 15);

        return match ($floor) {
            0 => 'Ground',
            1 => '1st Floor',
            2 => '2nd Floor',
            3 => '3rd Floor',
            default => $floor . 'th Floor',
        };
    }

    protected function makeBusinessName(): string
    {
        return fake()->company() . ' ' . fake()->randomElement(['Ltd', 'Holdings', 'Ventures', 'Supplies', 'Consulting']);
    }

    protected function makeReference(): string
    {
        do {
            $reference = 'TXN-' . strtoupper(Str::random(6));
        } while (Transaction::where('reference_code', $reference)->exists());

        return $reference;
    }

    protected function weightedPick(array $weights): string
    {
        $roll = random_int(1, array_sum($weights));
        $running = 0;

        foreach ($weights as $value => $weight) {
            $running += $weight;

            if ($roll <= $running) {
                return $value;
            }
        }

        return array_key_first($weights);
    }
}
