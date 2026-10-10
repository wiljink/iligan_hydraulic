<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\ReceivablesService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Demo customers with a realistic account history: sales invoices, charge invoices,
 * collection receipts and credit memos spread over the last two months.
 *
 * Every event runs through the real checkout/receivables services with the clock set
 * to the event date, so document numbers, dates, stock and balances stay consistent.
 * Safe to re-run: customers that already exist are skipped.
 *
 *   php artisan db:seed --class=DemoCustomersSeeder
 */
class DemoCustomersSeeder extends Seeder
{
    private Branch $branch;

    private User $admin;

    private User $cashier;

    public function run(): void
    {
        $this->branch = Branch::where('code', 'MAIN')->firstOrFail();
        $this->admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
        $this->cashier = User::where('role', User::ROLE_CASHIER)->first() ?? $this->admin;

        foreach ($this->customers() as $data) {
            if (Customer::where('name', $data['name'])->exists()) {
                continue;
            }

            $history = $data['history'];
            unset($data['history']);
            $customer = Customer::create($data);

            $sales = [];
            foreach ($history as $event) {
                $this->at($event['days_ago'], $event['time'] ?? '10:00', function () use ($event, $customer, &$sales) {
                    match ($event['type']) {
                        'sale' => $sales[] = $this->sale($customer, $event),
                        'pay' => app(ReceivablesService::class)->recordPayment(
                            $sales[$event['sale']], $this->amount($sales[$event['sale']], $event['amount']), $event['method'] ?? 'cash', $event['ref'] ?? null
                        ),
                        'return' => app(ReceivablesService::class)->issueCreditMemo(
                            $sales[$event['sale']], $this->returnLines($sales[$event['sale']], $event['items']), $event['reason'], 'Store Manager'
                        ),
                    };
                });
            }
        }

        Carbon::setTestNow();
        Auth::forgetUser();
    }

    private function at(int $daysAgo, string $time, callable $callback): void
    {
        [$hour, $minute] = explode(':', $time);
        Carbon::setTestNow(Carbon::now()->subDays($daysAgo)->setTime((int) $hour, (int) $minute));

        $callback();

        Carbon::setTestNow();
    }

    private function sale(Customer $customer, array $event): Sale
    {
        $method = $event['method'] ?? 'credit';
        Auth::setUser($method === 'credit' ? $this->admin : $this->cashier);

        $items = [];
        $subtotal = 0;
        foreach ($event['items'] as $name => $qty) {
            $product = Product::where('name', $name)->firstOrFail();
            $items[] = ['product_id' => $product->id, 'quantity' => $qty];
            $subtotal += $product->selling_price * $qty;
        }

        // Cash customers pay the exact total (rounded up to the next ₱100, so there is change).
        $total = round($subtotal * (1 + (float) config('pos.tax_rate')), 2);
        $paid = $method === 'credit' ? ($event['down_payment'] ?? 0) : ceil($total / 100) * 100;

        return app(CheckoutService::class)->checkout($items, [
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'payment_method' => $method,
            'amount_paid' => $paid,
            'payment_reference' => $event['ref'] ?? null,
        ]);
    }

    /** 'full' settles whatever is left on the invoice. */
    private function amount(Sale $sale, float|string $amount): float
    {
        Auth::setUser($this->admin);

        return $amount === 'full' ? $sale->fresh()->balance() : (float) $amount;
    }

    /**
     * @param  array<string, int>  $items  product name => quantity to return
     * @return array<int, int> sale_item_id => quantity
     */
    private function returnLines(Sale $sale, array $items): array
    {
        Auth::setUser($this->admin);

        return $sale->items
            ->filter(fn ($item) => isset($items[$item->product_name]))
            ->mapWithKeys(fn ($item) => [$item->id => $items[$item->product_name]])
            ->all();
    }

    private function customers(): array
    {
        return [
            [
                'name' => 'Mindanao Haulers Corporation',
                'address' => 'Km. 5 National Highway, Bulua, Cagayan de Oro City',
                'tin_number' => '204-118-552-000',
                'phone' => '(088) 856-2201',
                'email' => 'purchasing@mindanaohaulers.test',
                'contact_person' => 'Engr. Ramon Villanueva',
                'history' => [
                    ['type' => 'sale', 'days_ago' => 42, 'items' => ['Hydraulic Hose 1/2" (per meter)' => 12, 'JIC Fitting 1/2" Male' => 8, 'Hydraulic Oil Filter' => 4]],
                    ['type' => 'pay', 'days_ago' => 30, 'sale' => 0, 'amount' => 2000, 'method' => 'check', 'ref' => 'BDO Chk #004512'],
                    ['type' => 'sale', 'days_ago' => 16, 'items' => ['Fuel Filter - Isuzu Elf' => 6, 'Brake Shoe - Isuzu Elf' => 2, 'Engine Oil 15W-40 (per liter)' => 20]],
                    ['type' => 'return', 'days_ago' => 13, 'sale' => 1, 'items' => ['Brake Shoe - Isuzu Elf' => 1], 'reason' => 'Wrong size - for Elf 4HF1, ordered 4JB1'],
                    ['type' => 'pay', 'days_ago' => 9, 'sale' => 0, 'amount' => 2000, 'method' => 'check', 'ref' => 'BDO Chk #004583'],
                ],
            ],
            [
                'name' => 'Northern Mindanao Construction Supply',
                'address' => 'Lapasan Highway, Cagayan de Oro City',
                'tin_number' => '187-904-331-000',
                'phone' => '(088) 723-4410',
                'email' => 'accounts@nmcs.test',
                'contact_person' => 'Liza Gonzaga',
                'history' => [
                    ['type' => 'sale', 'days_ago' => 58, 'items' => ['Hydraulic Cylinder 2"x10"' => 1, 'Hydraulic Hose 3/8" (per meter)' => 10, 'NPT Fitting 3/8" Female' => 12]],
                    ['type' => 'pay', 'days_ago' => 44, 'sale' => 0, 'amount' => 5000, 'method' => 'bank_transfer', 'ref' => 'BPI-REF 77812'],
                    ['type' => 'pay', 'days_ago' => 28, 'sale' => 0, 'amount' => 'full', 'method' => 'bank_transfer', 'ref' => 'BPI-REF 78344'],
                    ['type' => 'sale', 'days_ago' => 6, 'method' => 'cash', 'items' => ['Oil Seal 30x45x7' => 10, 'O-Ring Kit Assorted' => 2]],
                ],
            ],
            [
                'name' => 'Cagayan Agri Equipment Services',
                'address' => 'Purok 3, Barangay Puerto, Cagayan de Oro City',
                'tin_number' => '312-550-781-000',
                'phone' => '0917-632-8890',
                'email' => 'cagayanagri@mail.test',
                'contact_person' => 'Danilo Ebarle',
                'history' => [
                    ['type' => 'sale', 'days_ago' => 37, 'items' => ['Hydraulic Oil AW46 (per liter)' => 40, 'Hydraulic Oil Filter' => 3, 'Quick Coupler 1/2"' => 1]],
                    ['type' => 'sale', 'days_ago' => 4, 'items' => ['Wheel Bearing Front' => 2, 'Alternator Belt' => 2]],
                ],
            ],
            [
                'name' => 'Lim Brothers Auto Repair',
                'address' => '23 Tiano Brothers St., Cagayan de Oro City',
                'tin_number' => '415-276-093-000',
                'phone' => '0928-441-7765',
                'email' => 'limbrosauto@mail.test',
                'contact_person' => 'Jonathan Lim',
                'history' => [
                    ['type' => 'sale', 'days_ago' => 21, 'items' => ['Brake Pad Set - Toyota Hilux' => 3, 'Glow Plug - Isuzu' => 8], 'down_payment' => 2000],
                    ['type' => 'pay', 'days_ago' => 11, 'sale' => 0, 'amount' => 1500, 'method' => 'gcash', 'ref' => 'GCash 1009 442 871'],
                    ['type' => 'sale', 'days_ago' => 3, 'method' => 'cash', 'items' => ['Engine Oil 15W-40 (per liter)' => 8, 'Oil Seal 30x45x7' => 4]],
                ],
            ],
            [
                'name' => 'Santos Trucking Services',
                'address' => 'Barangay Kauswagan, Cagayan de Oro City',
                'tin_number' => '226-781-340-000',
                'phone' => '0917-882-1043',
                'email' => 'santostrucking@mail.test',
                'contact_person' => 'Maria Santos',
                'history' => [
                    ['type' => 'sale', 'days_ago' => 26, 'items' => ['Fuel Filter - Isuzu Elf' => 4, 'Brake Shoe - Isuzu Elf' => 2, 'Hydraulic Oil AW46 (per liter)' => 10]],
                    ['type' => 'pay', 'days_ago' => 5, 'sale' => 0, 'amount' => 'full', 'method' => 'gcash', 'ref' => 'GCash 1012 004 559'],
                    ['type' => 'sale', 'days_ago' => 1, 'method' => 'gcash', 'items' => ['Alternator Belt' => 1], 'ref' => 'GCash 1013 220 017'],
                ],
            ],
            [
                'name' => 'Iligan Steel Fabricators Inc.',
                'address' => 'Barangay Tambo, Iligan City',
                'tin_number' => '198-660-215-000',
                'phone' => '(063) 221-5678',
                'email' => 'purchasing@iliganstelfab.test',
                'contact_person' => 'Arnel Quiño',
                'history' => [
                    ['type' => 'sale', 'days_ago' => 50, 'items' => ['Hydraulic Pressure Gauge' => 1, 'Hydraulic Hose 1/2" (per meter)' => 6, 'JIC Fitting 1/2" Male' => 6]],
                    ['type' => 'return', 'days_ago' => 47, 'sale' => 0, 'items' => ['Hydraulic Pressure Gauge' => 1], 'reason' => 'Defective - needle stuck at zero'],
                    ['type' => 'pay', 'days_ago' => 33, 'sale' => 0, 'amount' => 'full', 'method' => 'check', 'ref' => 'Metrobank Chk #118204'],
                ],
            ],
            [
                'name' => 'Jaime Uy',
                'address' => 'Zone 2, Barangay Gusa, Cagayan de Oro City',
                'tin_number' => null,
                'phone' => '0935-118-2290',
                'email' => null,
                'contact_person' => null,
                'history' => [
                    ['type' => 'sale', 'days_ago' => 19, 'method' => 'cash', 'items' => ['Glow Plug - Isuzu' => 4, 'Oil Seal 30x45x7' => 2]],
                    ['type' => 'sale', 'days_ago' => 12, 'method' => 'cash', 'items' => ['Hydraulic Oil Filter' => 2, 'Fuel Filter - Isuzu Elf' => 2]],
                    ['type' => 'return', 'days_ago' => 11, 'sale' => 1, 'items' => ['Fuel Filter - Isuzu Elf' => 1], 'reason' => 'Bought extra - unopened, refunded in cash'],
                    ['type' => 'sale', 'days_ago' => 2, 'method' => 'cash', 'items' => ['O-Ring Kit Assorted' => 1]],
                ],
            ],
            [
                'name' => 'Pedro Garcia',
                'address' => 'Barangay Carmen, Cagayan de Oro City',
                'tin_number' => null,
                'phone' => '0919-556-0034',
                'email' => null,
                'contact_person' => null,
                'history' => [
                    ['type' => 'sale', 'days_ago' => 7, 'method' => 'cash', 'items' => ['Engine Oil 15W-40 (per liter)' => 4]],
                ],
            ],
        ];
    }
}
