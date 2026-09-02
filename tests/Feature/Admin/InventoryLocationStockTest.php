<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\InventoryLocation;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A warehouse holds products, and the locations screen is where staff say which
 * ones. inventory_stocks was only ever written by the seeder before this, so the
 * warehouse page described a fraction of the catalogue and nothing an admin did
 * ever changed it. The rule these tests pin down: a warehouse line and the
 * product total the storefront sells from always move together.
 */
class InventoryLocationStockTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private InventoryLocation $main;
    private InventoryLocation $overflow;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Stock',
            'last_name' => 'Keeper',
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->main = InventoryLocation::create([
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'type' => 'warehouse',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->overflow = InventoryLocation::create([
            'name' => 'Overflow Store',
            'code' => 'WH-OVER',
            'type' => 'store',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Stock Test Category',
            'slug' => 'stock-test-category',
            'is_active' => true,
        ]);

        // The product's opening stock lands on the default shelf, which is what
        // the backfill migration does for the catalogue that already exists.
        $this->product = Product::create([
            'name' => 'Warehouse Kurti',
            'slug' => 'warehouse-kurti',
            'sku' => 'WK-001',
            'price' => 999,
            'mrp' => 1299,
            'stock_quantity' => 20,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    public function test_a_location_lists_the_products_it_holds(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.inventory.locations.show', $this->main))
            ->assertOk()
            ->assertSee('Warehouse Kurti')
            ->assertSee('WK-001');
    }

    public function test_opening_stock_lands_on_the_default_shelf(): void
    {
        $this->assertSame(20, $this->lineAt($this->main)?->quantity);
    }

    public function test_adding_a_product_stocks_it_and_raises_the_product_total(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.inventory.locations.stock.store', $this->overflow), [
                'product_id' => $this->product->id,
                'quantity' => 5,
                'reason' => 'Received from supplier',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(5, $this->lineAt($this->overflow)?->quantity);
        $this->assertSame(25, $this->product->fresh()->stock_quantity);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'location_id' => $this->overflow->id,
            'type' => 'in',
            'quantity' => 5,
            'reason' => 'Received from supplier',
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_stocking_the_same_product_again_tops_up_one_line(): void
    {
        foreach ([5, 3] as $quantity) {
            $this->actingAs($this->adminUser, 'admin')
                ->post(route('admin.inventory.locations.stock.store', $this->overflow), [
                    'product_id' => $this->product->id,
                    'quantity' => $quantity,
                ])
                ->assertSessionHasNoErrors();
        }

        // MySQL lets a second NULL-variant row past the unique index, so the
        // merge has to be deliberate - two lines would count the same units twice.
        $this->assertSame(1, InventoryStock::where('product_id', $this->product->id)
            ->where('location_id', $this->overflow->id)
            ->count());
        $this->assertSame(8, $this->lineAt($this->overflow)?->quantity);
        $this->assertSame(28, $this->product->fresh()->stock_quantity);
    }

    public function test_a_size_from_another_product_is_rejected(): void
    {
        $other = Product::create([
            'name' => 'Other Kurti',
            'slug' => 'other-kurti',
            'sku' => 'OK-001',
            'price' => 499,
            'mrp' => 699,
            'stock_quantity' => 0,
            'category_id' => $this->product->category_id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $other->id,
            'name' => 'M',
            'sku' => 'OK-001-M',
            'stock_quantity' => 4,
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.inventory.locations.stock.store', $this->overflow), [
                'product_id' => $this->product->id,
                'variant_id' => $variant->id,
                'quantity' => 2,
            ])
            ->assertSessionHasErrors('variant_id', null, 'addStock');

        $this->assertDatabaseMissing('inventory_stocks', [
            'product_id' => $this->product->id,
            'variant_id' => $variant->id,
        ]);
    }

    public function test_a_size_of_the_same_product_gets_its_own_line(): void
    {
        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'L',
            'sku' => 'WK-001-L',
            'stock_quantity' => 6,
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.inventory.locations.stock.store', $this->main), [
                'product_id' => $this->product->id,
                'variant_id' => $variant->id,
                'quantity' => 4,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('inventory_stocks', [
            'product_id' => $this->product->id,
            'variant_id' => $variant->id,
            'location_id' => $this->main->id,
            'quantity' => 4,
        ]);

        // The size's own figure moves, not the product's.
        $this->assertSame(10, $variant->fresh()->stock_quantity);
        $this->assertSame(20, $this->product->fresh()->stock_quantity);
        $this->assertSame(20, $this->lineAt($this->main)?->quantity);
    }

    public function test_setting_a_line_moves_the_product_total_by_the_difference(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.inventory.locations.stock.update', [$this->main, $this->lineAt($this->main)]), [
                'type' => 'set',
                'quantity' => 12,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(12, $this->lineAt($this->main)?->quantity);
        $this->assertSame(12, $this->product->fresh()->stock_quantity);
    }

    public function test_removing_more_than_the_shelf_holds_is_rejected(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.inventory.locations.stock.update', [$this->main, $this->lineAt($this->main)]), [
                'type' => 'remove',
                'quantity' => 99,
            ])
            ->assertSessionHasErrors('quantity', null, 'adjustStock');

        $this->assertSame(20, $this->lineAt($this->main)?->quantity);
        $this->assertSame(20, $this->product->fresh()->stock_quantity);
    }

    public function test_taking_a_product_off_a_shelf_takes_its_units_out_of_stock(): void
    {
        $line = $this->lineAt($this->main);

        $this->actingAs($this->adminUser, 'admin')
            ->delete(route('admin.inventory.locations.stock.destroy', [$this->main, $line]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('inventory_stocks', ['id' => $line->id]);
        $this->assertSame(0, $this->product->fresh()->stock_quantity);
    }

    public function test_a_line_cannot_be_adjusted_through_another_location(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.inventory.locations.stock.update', [$this->overflow, $this->lineAt($this->main)]), [
                'type' => 'set',
                'quantity' => 1,
            ])
            ->assertNotFound();

        $this->assertSame(20, $this->lineAt($this->main)?->quantity);
    }

    public function test_a_location_holding_stock_cannot_be_deleted(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->from(route('admin.inventory.locations.index'))
            ->delete(route('admin.inventory.locations.destroy', $this->main))
            ->assertRedirect(route('admin.inventory.locations.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('inventory_locations', ['id' => $this->main->id]);
    }

    public function test_an_empty_location_can_be_deleted(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->delete(route('admin.inventory.locations.destroy', $this->overflow))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('inventory_locations', ['id' => $this->overflow->id]);
    }

    public function test_a_sale_comes_off_the_shelf_it_was_held_on(): void
    {
        // How checkout takes stock down: decrement() on the model, which fires
        // "updated" but never "saved".
        $this->product->decrement('stock_quantity', 3);

        $this->assertSame(17, $this->lineAt($this->main)?->quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'location_id' => $this->main->id,
            'type' => 'out',
            'quantity' => 3,
        ]);
    }

    public function test_stock_edited_on_the_product_form_follows_onto_the_shelf(): void
    {
        $this->product->update(['stock_quantity' => 30]);

        $this->assertSame(30, $this->lineAt($this->main)?->quantity);
    }

    public function test_the_product_level_adjustment_names_the_location_it_happens_at(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.inventory.update-stock', $this->product), [
                'location_id' => $this->overflow->id,
                'type' => 'add',
                'quantity' => 7,
                'reason' => 'Transfer in',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(7, $this->lineAt($this->overflow)?->quantity);
        $this->assertSame(20, $this->lineAt($this->main)?->quantity);
        $this->assertSame(27, $this->product->fresh()->stock_quantity);
    }

    public function test_the_inventory_list_can_be_filtered_to_one_location(): void
    {
        $elsewhere = Product::create([
            'name' => 'Unstocked Dupatta',
            'slug' => 'unstocked-dupatta',
            'sku' => 'UD-001',
            'price' => 299,
            'mrp' => 399,
            'stock_quantity' => 0,
            'category_id' => $this->product->category_id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.inventory.index', ['location' => $this->main->id]))
            ->assertOk()
            ->assertSee('WK-001')
            ->assertDontSee('UD-001');
    }

    public function test_movements_are_recorded_against_the_location(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.inventory.locations.stock.update', [$this->main, $this->lineAt($this->main)]), [
                'type' => 'remove',
                'quantity' => 4,
                'reason' => 'Damaged',
            ])
            ->assertSessionHasNoErrors();

        $movement = InventoryMovement::where('reason', 'Damaged')->firstOrFail();

        $this->assertSame($this->main->id, $movement->location_id);
        $this->assertSame('out', $movement->type);
        $this->assertSame(20, $movement->quantity_before);
        $this->assertSame(16, $movement->quantity_after);
        $this->assertSame(16, $this->product->fresh()->stock_quantity);
    }

    public function test_selling_a_size_comes_off_that_sizes_shelf(): void
    {
        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'M',
            'sku' => 'WK-001-M',
            'stock_quantity' => 6,
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.inventory.locations.stock.store', $this->main), [
                'product_id' => $this->product->id,
                'variant_id' => $variant->id,
                'quantity' => 4,
            ])
            ->assertSessionHasNoErrors();

        $variant->fresh()->decrement('stock_quantity', 2);

        $this->assertSame(2, $this->variantLineAt($this->main, $variant)?->quantity);
        // The product's own line mirrors the product's own figure, which a size
        // sale never touches.
        $this->assertSame(20, $this->lineAt($this->main)?->quantity);
    }

    public function test_a_sold_out_size_is_restocked_on_the_shelf_it_emptied(): void
    {
        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'XL',
            'sku' => 'WK-001-XL',
            'stock_quantity' => 2,
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.inventory.locations.stock.store', $this->main), [
                'product_id' => $this->product->id,
                'variant_id' => $variant->id,
                'quantity' => 2,
            ])
            ->assertSessionHasNoErrors();

        $variant->fresh()->decrement('stock_quantity', 2);
        $this->assertSame(0, $this->variantLineAt($this->main, $variant)?->quantity);

        $variant->fresh()->increment('stock_quantity', 3);

        // An empty shelf is still that size's shelf - the restock belongs there
        // rather than nowhere.
        $this->assertSame(3, $this->variantLineAt($this->main, $variant)?->quantity);
    }

    public function test_a_size_nobody_shelved_is_not_given_a_shelf_behind_their_back(): void
    {
        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'S',
            'sku' => 'WK-001-S',
            'stock_quantity' => 6,
            'is_active' => true,
        ]);

        $variant->update(['stock_quantity' => 9]);

        // Those units are already counted on the product's line; a second line
        // for the size would count them twice.
        $this->assertSame(0, InventoryStock::whereNotNull('variant_id')->count());
        $this->assertSame(20, $this->lineAt($this->main)?->quantity);
    }

    public function test_the_first_location_takes_the_stock_the_shop_already_has(): void
    {
        // A shop that starts tracking warehouses today still has stock, and it
        // is all in the one place it just named.
        InventoryStock::query()->delete();
        InventoryLocation::query()->delete();

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.inventory.locations.store'), [
                'name' => 'First Warehouse',
                'code' => 'WH-FIRST',
                'is_active' => 1,
            ])
            ->assertSessionHasNoErrors();

        $created = InventoryLocation::where('code', 'WH-FIRST')->firstOrFail();

        $this->assertTrue((bool) $created->is_default);
        $this->assertSame(20, $this->lineAt($created)?->quantity);
        $this->assertSame(20, $this->product->fresh()->stock_quantity);
    }

    public function test_a_later_location_starts_empty(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.inventory.locations.store'), [
                'name' => 'Third Warehouse',
                'code' => 'WH-THIRD',
                'is_active' => 1,
            ])
            ->assertSessionHasNoErrors();

        $created = InventoryLocation::where('code', 'WH-THIRD')->firstOrFail();

        $this->assertFalse((bool) $created->is_default);
        $this->assertSame(0, InventoryStock::where('location_id', $created->id)->count());
        $this->assertSame(20, $this->lineAt($this->main)?->quantity);
    }

    public function test_a_refused_adjustment_does_not_speak_for_the_add_product_form(): void
    {
        // Both forms on the page post a "quantity". Sharing one error bag put
        // the adjustment's message and its number on the Add card, which would
        // then have added that number to a product's sellable stock.
        $this->actingAs($this->adminUser, 'admin')
            ->put(route('admin.inventory.locations.stock.update', [$this->main, $this->lineAt($this->main)]), [
                'type' => 'remove',
                'quantity' => 99,
            ])
            ->assertSessionHasErrors('quantity', null, 'adjustStock')
            ->assertSessionDoesntHaveErrors('quantity', null, 'addStock');
    }

    public function test_deleting_a_size_clears_the_shelves_it_was_on(): void
    {
        $variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'XS',
            'sku' => 'WK-001-XS',
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.inventory.locations.stock.store', $this->main), [
                'product_id' => $this->product->id,
                'variant_id' => $variant->id,
                'quantity' => 3,
            ])
            ->assertSessionHasNoErrors();

        $variant->delete();

        // inventory_stocks.variant_id has no foreign key to cascade on, and a
        // surviving row renders as "All sizes" - a phantom line beside the
        // product's own that inflates the location's totals and blocks it from
        // being deleted.
        $this->assertSame(0, InventoryStock::where('variant_id', $variant->id)->count());
        $this->assertDatabaseHas('inventory_movements', [
            'variant_id' => $variant->id,
            'location_id' => $this->main->id,
            'type' => 'out',
            'quantity' => 3,
            'reason' => 'Removed from catalogue',
        ]);
    }

    public function test_the_adjust_dialog_is_told_what_each_warehouse_holds(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.inventory.locations.stock.store', $this->overflow), [
                'product_id' => $this->product->id,
                'quantity' => 6,
            ])
            ->assertSessionHasNoErrors();

        $product = Product::with('inventoryStocks')->findOrFail($this->product->id);

        // The dialog sets stock at one location, so the baseline it shows has
        // to be that location's rather than the product's total of 26.
        $this->assertSame([
            (string) $this->main->id => 20,
            (string) $this->overflow->id => 6,
        ], \App\Http\Controllers\Admin\InventoryController::heldByLocation($product));
    }

    private function lineAt(InventoryLocation $location): ?InventoryStock
    {
        return InventoryStock::where('product_id', $this->product->id)
            ->whereNull('variant_id')
            ->where('location_id', $location->id)
            ->first();
    }

    private function variantLineAt(InventoryLocation $location, ProductVariant $variant): ?InventoryStock
    {
        return InventoryStock::where('variant_id', $variant->id)
            ->where('location_id', $location->id)
            ->first();
    }
}
