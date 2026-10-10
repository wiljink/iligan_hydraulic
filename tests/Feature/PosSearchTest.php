<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_search_finds_product_by_any_oem_number(): void
    {
        Product::create([
            'name' => 'VIC Oil Filter C-226',
            'part_number' => 'C-226',
            'oem_number' => '15208-20N03, 15208-40L02, 8-97356-545-0',
            'unit' => 'pcs',
            'cost_price' => 100,
            'selling_price' => 150,
        ]);
        Product::create(['name' => 'Unrelated Hose', 'unit' => 'pcs', 'cost_price' => 10, 'selling_price' => 20]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->getJson(route('pos.search', ['term' => '8-97356-545-0']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'VIC Oil Filter C-226')
            ->assertJsonPath('0.part_number', 'C-226');

        $this->getJson(route('pos.search', ['term' => '15208-40L02']))
            ->assertOk()
            ->assertJsonCount(1);
    }
}
