<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CheckoutSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_guest_can_place_valid_order_with_server_computed_prices()
    {
        $product = Product::where('status', '!=', 'Out of stock')->where('stock', '>', 5)->first();
        if (! $product) {
            $product = Product::create([
                'slug' => 'test-signature-tea',
                'name' => 'Test Signature Tea',
                'price' => 850,
                'old_price' => 950,
                'stock' => 50,
                'status' => 'In stock',
                'category' => 'Black Tea',
                'weight' => '250g',
                'rating' => 5,
                'reviews' => 12,
                'blurb' => 'Test blurb',
                'image' => 'images/garden.jpg',
                'tag' => 'Premium',
            ]);
        }

        $payload = [
            'items' => [
                ['id' => $product->slug, 'qty' => 2],
            ],
            'name' => 'Tanvir Ahmed',
            'email' => 'tanvir@example.com',
            'phone' => '01712345678',
            'address' => 'House 12, Road 4, Dhanmondi',
            'city' => 'Dhaka',
            'delivery_zone' => 'inside',
            'payment_method' => 'Cash on Delivery',
            'note' => 'Please deliver in the afternoon',
        ];

        $response = $this->postJson('/api/public/checkout', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.subtotal', $product->price * 2)
            ->assertJsonPath('data.status', 'Pending');

        $this->assertDatabaseHas('orders', [
            'customer_email' => 'tanvir@example.com',
            'phone' => '01712345678',
            'subtotal' => $product->price * 2,
        ]);
    }

    public function test_honeypot_trap_blocks_spam_bots()
    {
        $product = Product::first();

        $payload = [
            'items' => [
                ['id' => $product->slug, 'qty' => 1],
            ],
            'name' => 'Bot Spammer',
            'email' => 'spambot@example.com',
            'phone' => '01700000000',
            'address' => 'Random Spam Address 123',
            'city' => 'Dhaka',
            'website' => 'http://spam-link.ru', // Honeypot trap filled
        ];

        $response = $this->postJson('/api/public/checkout', $payload);
        $response->assertStatus(422);
    }

    public function test_invalid_phone_number_is_rejected()
    {
        $product = Product::first();

        $payload = [
            'items' => [
                ['id' => $product->slug, 'qty' => 1],
            ],
            'name' => 'Customer Name',
            'email' => 'valid@example.com',
            'phone' => 'invalid-phone-abc', // Invalid phone format
            'address' => 'Valid House Address',
            'city' => 'Dhaka',
        ];

        $response = $this->postJson('/api/public/checkout', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_xss_scripts_in_customer_fields_are_sanitized()
    {
        $product = Product::where('status', '!=', 'Out of stock')->where('stock', '>', 5)->first();

        $payload = [
            'items' => [
                ['id' => $product->slug, 'qty' => 1],
            ],
            'name' => '<b>Hacker</b><script>alert("XSS")</script>',
            'email' => 'security_test@example.com',
            'phone' => '01811223344',
            'address' => '<img src=x onerror=alert(1)>Flat 4B, Banani',
            'city' => 'Dhaka',
            'note' => '<iframe src="evil.com"></iframe>Urgent delivery',
        ];

        $response = $this->postJson('/api/public/checkout', $payload);
        $response->assertStatus(201);

        $order = Order::where('customer_email', 'security_test@example.com')->latest()->first();
        $this->assertNotNull($order);
        $this->assertStringNotContainsString('<script>', $order->customer_name);
        $this->assertStringNotContainsString('alert("XSS")', $order->customer_name);
        $this->assertStringNotContainsString('<img', $order->address);
        $this->assertStringNotContainsString('<iframe', $order->note);
    }

    public function test_cannot_order_out_of_stock_product()
    {
        $product = Product::where('status', 'Out of stock')->orWhere('stock', 0)->first();
        if (! $product) {
            $product = Product::create([
                'sku' => 'CK-OUT-TEST',
                'slug' => 'out-of-stock-tea',
                'name' => 'Sold Out Tea',
                'price' => 900,
                'stock' => 0,
                'status' => 'Out of stock',
                'category' => 'Green Tea',
                'weight' => '100g',
                'rating' => 5,
                'reviews' => 1,
                'blurb' => 'Sold out',
                'image' => 'images/green.jpg',
                'tag' => 'Sold Out',
            ]);
        }

        $payload = [
            'items' => [
                ['id' => $product->slug, 'qty' => 1],
            ],
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'phone' => '01911223344',
            'address' => 'Mirpur 10, Dhaka',
            'city' => 'Dhaka',
        ];

        $response = $this->postJson('/api/public/checkout', $payload);
        $response->assertStatus(422);
    }
}
