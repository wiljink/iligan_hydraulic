<?php

namespace Database\Seeders;

use App\Models\AccountPayable;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountPayableSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();
        $manager = User::where('role', User::ROLE_MANAGER)->first() ?? $admin;

        $suppliers = Supplier::all();
        if ($suppliers->isEmpty()) {
            return;
        }

        $s1 = $suppliers->first();
        $s2 = $suppliers->skip(1)->first() ?? $s1;
        $s3 = $suppliers->skip(2)->first() ?? $s1;

        $payable1 = AccountPayable::where('bill_number', 'BILL-202610-001')->first();
        if (! $payable1) {
            $payable1 = AccountPayable::create([
                'supplier_id' => $s1->id,
                'vendor_name' => $s1->name,
                'bill_number' => 'BILL-202610-001',
                'bill_date' => now()->subDays(20)->toDateString(),
                'due_date' => now()->subDays(5)->toDateString(), // Overdue
                'amount' => 45000.00,
                'amount_paid' => 15000.00,
                'status' => AccountPayable::STATUS_PARTIALLY_PAID,
                'notes' => 'Bulk shipment of hydraulic hoses and JIC fittings.',
                'user_id' => $admin?->id,
            ]);
        }

        if (\App\Models\Purchase::where('purchase_number', 'PO-202610-0001')->doesntExist()) {
            $purchase = \App\Models\Purchase::create([
                'purchase_number' => 'PO-202610-0001',
                'supplier_id' => $s1->id,
                'supplier_name' => $s1->name,
                'reference_number' => 'DR-9921',
                'receipt_date' => now()->subDays(20)->toDateString(),
                'total_amount' => 45000.00,
                'payment_status' => \App\Models\Purchase::PAYMENT_PARTIAL,
                'payment_method' => 'cash',
                'amount_paid' => 15000.00,
                'account_payable_id' => $payable1->id,
                'due_date' => now()->subDays(5)->toDateString(),
                'status' => \App\Models\Purchase::STATUS_RECEIVED,
                'notes' => 'Received by warehouse team. Partial advance paid.',
                'user_id' => $admin?->id,
            ]);

            $hose = \App\Models\Product::where('name', 'like', '%Hydraulic Hose%')->first();
            if ($hose) {
                $purchase->items()->create([
                    'product_id' => $hose->id,
                    'product_name' => $hose->name,
                    'product_sku' => $hose->sku,
                    'quantity' => 150,
                    'unit_cost' => 150.00,
                    'subtotal' => 22500.00,
                ]);
            }

            $fitting = \App\Models\Product::where('name', 'like', '%Fitting%')->first();
            if ($fitting) {
                $purchase->items()->create([
                    'product_id' => $fitting->id,
                    'product_name' => $fitting->name,
                    'product_sku' => $fitting->sku,
                    'quantity' => 500,
                    'unit_cost' => 45.00,
                    'subtotal' => 22500.00,
                ]);
            }
        }

        if (AccountPayable::where('bill_number', 'BILL-202610-002')->doesntExist()) {
            AccountPayable::create([
                'supplier_id' => $s2->id,
                'vendor_name' => $s2->name,
                'bill_number' => 'BILL-202610-002',
                'bill_date' => now()->subDays(10)->toDateString(),
                'due_date' => now()->addDays(3)->toDateString(), // Due soon
                'amount' => 28500.00,
                'amount_paid' => 0.00,
                'status' => AccountPayable::STATUS_UNPAID,
                'notes' => 'Replacement filters and brake pad inventory stock.',
                'user_id' => $manager?->id,
            ]);
        }

        if (AccountPayable::where('bill_number', 'BILL-202610-003')->doesntExist()) {
            AccountPayable::create([
                'supplier_id' => $s3->id,
                'vendor_name' => $s3->name,
                'bill_number' => 'BILL-202610-003',
                'bill_date' => now()->subDays(3)->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'amount' => 62000.00,
                'amount_paid' => 0.00,
                'status' => AccountPayable::STATUS_UNPAID,
                'notes' => 'Hydraulic gear pumps and cylinder components order.',
                'user_id' => $admin?->id,
            ]);
        }

        if (AccountPayable::where('bill_number', 'BILL-202609-088')->doesntExist()) {
            AccountPayable::create([
                'supplier_id' => $s1->id,
                'vendor_name' => $s1->name,
                'bill_number' => 'BILL-202609-088',
                'bill_date' => now()->subDays(40)->toDateString(),
                'due_date' => now()->subDays(10)->toDateString(),
                'amount' => 35000.00,
                'amount_paid' => 35000.00,
                'status' => AccountPayable::STATUS_PAID,
                'payment_method' => 'bank',
                'reference_number' => 'BDO-TRF-99412',
                'notes' => 'Settled in full via bank transfer.',
                'user_id' => $admin?->id,
            ]);
        }
    }
}
