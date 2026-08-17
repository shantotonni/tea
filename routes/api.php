<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\BlendOptionController;
use App\Http\Controllers\Api\BlendQuestionController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\CollectionNoteController;
use App\Http\Controllers\Api\CreationTileController;
use App\Http\Controllers\Api\InstaShotController;
use App\Http\Controllers\Api\PromoBannerController;
use App\Http\Controllers\Api\PromoCodeController;
use App\Http\Controllers\Api\SubscriberController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerGroupController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\FounderController;
use App\Http\Controllers\Api\FounderPointController;
use App\Http\Controllers\Api\HeroFeatureController;
use App\Http\Controllers\Api\HeroSlideController;
use App\Http\Controllers\Api\HeroStatController;
use App\Http\Controllers\Api\FooterLinkController;
use App\Http\Controllers\Api\MarqueeItemController;
use App\Http\Controllers\Api\NavLinkController;
use App\Http\Controllers\Api\OfferCampaignController;
use App\Http\Controllers\Api\SocialLinkController;
use App\Http\Controllers\Api\StoryPointController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProcessStepController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Cha Kunjo admin (JWT protected)
|--------------------------------------------------------------------------
*/

// public — storefront (no auth)
Route::post('login', [AuthController::class, 'login']);
Route::get('public/homepage', [PublicController::class, 'homepage']);
Route::get('public/products', [PublicController::class, 'products']);
Route::get('public/products/{slug}', [PublicController::class, 'product']);
Route::get('public/reviews', [PublicController::class, 'reviews']);
Route::get('public/faqs', [PublicController::class, 'faqs']);
Route::get('public/posts', [PublicController::class, 'posts']);
Route::get('public/posts/{id}', [PublicController::class, 'post']);
Route::get('public/process', [PublicController::class, 'process']);
Route::get('public/quotes', [PublicController::class, 'quotes']);
Route::get('public/founder', [PublicController::class, 'founder']);
Route::get('public/hero', [PublicController::class, 'hero']);
Route::get('public/marquee', [PublicController::class, 'marquee']);
Route::get('public/story', [PublicController::class, 'story']);
Route::get('public/blend-finder', [PublicController::class, 'blendFinder']);
Route::get('public/navbar', [PublicController::class, 'navbar']);
Route::get('public/footer', [PublicController::class, 'footerData']);
Route::get('public/seo', [PublicController::class, 'seo']);
Route::get('public/shipping', [PublicController::class, 'shipping']);
Route::get('public/settings', [PublicController::class, 'settings']);
Route::post('public/ai/chat', [PublicController::class, 'aiChat']);
Route::post('public/ai/pdp-ask', [PublicController::class, 'aiPdpQuestion']);
Route::post('public/ai/recommend', [PublicController::class, 'aiBlendRecommend']);
Route::post('public/ai/gift-note', [PublicController::class, 'aiGiftNote']);
Route::get('public/districts', [PublicController::class, 'districts']);
Route::get('public/sections', [PublicController::class, 'sections']);
Route::get('public/offer', [PublicController::class, 'offer']);
Route::get('public/recent-orders', [PublicController::class, 'recentOrders']);
Route::post('public/subscribe', [SubscriberController::class, 'store']);

// storefront customer auth (public)
Route::post('customer/register', [CustomerAuthController::class, 'register']);
Route::post('customer/login', [CustomerAuthController::class, 'login']);

// checkout (guest allowed; links customer when token sent) — throttled against order spam
Route::post('public/checkout', [CheckoutController::class, 'store'])->middleware('throttle:6,1');
Route::post('public/promo/validate', [PromoCodeController::class, 'validateCode'])->middleware('throttle:20,1');

// signed-in customer area
Route::middleware('auth:customer')->group(function () {
    Route::get('customer/me', [CustomerAuthController::class, 'me']);
    Route::put('customer/profile', [CustomerAuthController::class, 'updateProfile']);
    Route::post('customer/logout', [CustomerAuthController::class, 'logout']);
    Route::get('customer/orders', [CustomerAuthController::class, 'orders']);
    Route::get('customer/wishlist', [CustomerAuthController::class, 'getWishlist']);
    Route::post('customer/wishlist/toggle', [CustomerAuthController::class, 'toggleWishlist']);
});

// protected by JWT (auth:api guard = jwt driver)
Route::middleware('auth:api')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::put('profile', [AuthController::class, 'updateProfile']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('analytics', [DashboardController::class, 'analytics']);

    Route::apiResource('products', ProductController::class);
    Route::post('upload', function (\Illuminate\Http\Request $request) {
        $request->validate([
            // 'file' (not 'image') so servers whose GD lacks webp support don't
            // reject webp uploads — mimes still restricts to real image types.
            'image' => 'required|file|mimes:jpeg,png,jpg,webp,gif,svg,avif|max:10240',
        ]);

        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension());
        $name = time() . '_' . \Illuminate\Support\Str::random(6) . '.' . $ext;
        $targetDir = public_path('images/uploads');
        if (! file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $file->move($targetDir, $name);

        $savedPath = $targetDir . '/' . $name;
        if (in_array($ext, ['jpg', 'jpeg', 'png']) && function_exists('imagewebp')) {
            $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $savedPath);
            $img = $ext === 'png' ? @imagecreatefrompng($savedPath) : @imagecreatefromjpeg($savedPath);
            if ($img) {
                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);
                imagewebp($img, $webpPath, 82);
                imagedestroy($img);
            }
        }

        return response()->json([
            'url' => 'images/uploads/' . $name,
        ]);
    });

    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::put('orders/{order}', [OrderController::class, 'update']);

    Route::get('customers', [CustomerController::class, 'index']);
    Route::get('customers/{customer}', [CustomerController::class, 'show']);
    Route::post('customers', [CustomerController::class, 'store']);
    Route::put('customers/{customer}', [CustomerController::class, 'update']);
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy']);

    Route::get('promo-codes', [PromoCodeController::class, 'index']);
    Route::post('promo-codes', [PromoCodeController::class, 'store']);
    Route::put('promo-codes/{promo}', [PromoCodeController::class, 'update']);
    Route::delete('promo-codes/{promo}', [PromoCodeController::class, 'destroy']);

    Route::get('customer-groups', [CustomerGroupController::class, 'index']);
    Route::get('customer-groups/{group}', [CustomerGroupController::class, 'show']);
    Route::post('customer-groups', [CustomerGroupController::class, 'store']);
    Route::put('customer-groups/{group}', [CustomerGroupController::class, 'update']);
    Route::put('customer-groups/{group}/members', [CustomerGroupController::class, 'syncMembers']);
    Route::delete('customer-groups/{group}', [CustomerGroupController::class, 'destroy']);

    Route::get('settings', [SettingsController::class, 'index']);
    Route::put('settings/{group}', [SettingsController::class, 'update']);
    Route::post('settings/ai/test', [SettingsController::class, 'testAiConnection']);
    Route::post('ai/generate', [SettingsController::class, 'generateAiContent']);

    Route::get('reviews', [ReviewController::class, 'index']);
    Route::post('reviews', [ReviewController::class, 'store']);
    Route::put('reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('reviews/{review}', [ReviewController::class, 'destroy']);

    Route::get('faqs', [FaqController::class, 'index']);
    Route::post('faqs', [FaqController::class, 'store']);
    Route::put('faqs/{faq}', [FaqController::class, 'update']);
    Route::delete('faqs/{faq}', [FaqController::class, 'destroy']);

    Route::get('blog', [BlogController::class, 'index']);
    Route::post('blog', [BlogController::class, 'store']);
    Route::put('blog/{blog}', [BlogController::class, 'update']);
    Route::delete('blog/{blog}', [BlogController::class, 'destroy']);

    Route::get('process', [ProcessStepController::class, 'index']);
    Route::post('process', [ProcessStepController::class, 'store']);
    Route::put('process/{step}', [ProcessStepController::class, 'update']);
    Route::delete('process/{step}', [ProcessStepController::class, 'destroy']);

    Route::get('quotes', [QuoteController::class, 'index']);
    Route::post('quotes', [QuoteController::class, 'store']);
    Route::put('quotes/{quote}', [QuoteController::class, 'update']);
    Route::delete('quotes/{quote}', [QuoteController::class, 'destroy']);

    Route::get('founders', [FounderController::class, 'index']);
    Route::post('founders', [FounderController::class, 'store']);
    Route::put('founders/{founder}', [FounderController::class, 'update']);
    Route::delete('founders/{founder}', [FounderController::class, 'destroy']);

    Route::get('founder-points', [FounderPointController::class, 'index']);
    Route::post('founder-points', [FounderPointController::class, 'store']);
    Route::put('founder-points/{point}', [FounderPointController::class, 'update']);
    Route::delete('founder-points/{point}', [FounderPointController::class, 'destroy']);

    // hero
    Route::get('hero-slides', [HeroSlideController::class, 'index']);
    Route::post('hero-slides', [HeroSlideController::class, 'store']);
    Route::put('hero-slides/{slide}', [HeroSlideController::class, 'update']);
    Route::delete('hero-slides/{slide}', [HeroSlideController::class, 'destroy']);

    Route::get('hero-features', [HeroFeatureController::class, 'index']);
    Route::post('hero-features', [HeroFeatureController::class, 'store']);
    Route::put('hero-features/{feature}', [HeroFeatureController::class, 'update']);
    Route::delete('hero-features/{feature}', [HeroFeatureController::class, 'destroy']);

    Route::get('hero-stats', [HeroStatController::class, 'index']);
    Route::post('hero-stats', [HeroStatController::class, 'store']);
    Route::put('hero-stats/{stat}', [HeroStatController::class, 'update']);
    Route::delete('hero-stats/{stat}', [HeroStatController::class, 'destroy']);

    // marquee
    Route::get('marquee', [MarqueeItemController::class, 'index']);
    Route::post('marquee', [MarqueeItemController::class, 'store']);
    Route::put('marquee/{item}', [MarqueeItemController::class, 'update']);
    Route::delete('marquee/{item}', [MarqueeItemController::class, 'destroy']);

    // story points
    Route::get('story-points', [StoryPointController::class, 'index']);
    Route::post('story-points', [StoryPointController::class, 'store']);
    Route::put('story-points/{point}', [StoryPointController::class, 'update']);
    Route::delete('story-points/{point}', [StoryPointController::class, 'destroy']);

    // blend finder
    Route::get('blend-questions', [BlendQuestionController::class, 'index']);
    Route::post('blend-questions', [BlendQuestionController::class, 'store']);
    Route::put('blend-questions/{question}', [BlendQuestionController::class, 'update']);
    Route::delete('blend-questions/{question}', [BlendQuestionController::class, 'destroy']);

    Route::post('blend-options', [BlendOptionController::class, 'store']);
    Route::put('blend-options/{option}', [BlendOptionController::class, 'update']);
    Route::delete('blend-options/{option}', [BlendOptionController::class, 'destroy']);

    // navbar
    Route::get('nav-links', [NavLinkController::class, 'index']);
    Route::post('nav-links', [NavLinkController::class, 'store']);
    Route::put('nav-links/{link}', [NavLinkController::class, 'update']);
    Route::delete('nav-links/{link}', [NavLinkController::class, 'destroy']);

    // footer
    Route::get('footer-links', [FooterLinkController::class, 'index']);
    Route::post('footer-links', [FooterLinkController::class, 'store']);
    Route::put('footer-links/{link}', [FooterLinkController::class, 'update']);
    Route::delete('footer-links/{link}', [FooterLinkController::class, 'destroy']);

    // socials
    Route::get('social-links', [SocialLinkController::class, 'index']);
    Route::post('social-links', [SocialLinkController::class, 'store']);
    Route::put('social-links/{social}', [SocialLinkController::class, 'update']);
    Route::delete('social-links/{social}', [SocialLinkController::class, 'destroy']);

    // creation tiles
    Route::get('creation-tiles', [CreationTileController::class, 'index']);
    Route::post('creation-tiles', [CreationTileController::class, 'store']);
    Route::put('creation-tiles/{tile}', [CreationTileController::class, 'update']);
    Route::delete('creation-tiles/{tile}', [CreationTileController::class, 'destroy']);

    // promo banners
    Route::get('promo-banners', [PromoBannerController::class, 'index']);
    Route::post('promo-banners', [PromoBannerController::class, 'store']);
    Route::put('promo-banners/{banner}', [PromoBannerController::class, 'update']);
    Route::delete('promo-banners/{banner}', [PromoBannerController::class, 'destroy']);

    // instagram shots
    Route::get('insta-shots', [InstaShotController::class, 'index']);
    Route::post('insta-shots', [InstaShotController::class, 'store']);
    Route::put('insta-shots/{shot}', [InstaShotController::class, 'update']);
    Route::delete('insta-shots/{shot}', [InstaShotController::class, 'destroy']);

    // collection notes
    Route::get('collection-notes', [CollectionNoteController::class, 'index']);
    Route::post('collection-notes', [CollectionNoteController::class, 'store']);
    Route::put('collection-notes/{note}', [CollectionNoteController::class, 'update']);
    Route::delete('collection-notes/{note}', [CollectionNoteController::class, 'destroy']);

    // offer campaigns (occasion / festival offers)
    Route::get('offer-campaigns', [OfferCampaignController::class, 'index']);
    Route::get('offer-campaigns/{campaign}', [OfferCampaignController::class, 'show']);
    Route::post('offer-campaigns', [OfferCampaignController::class, 'store']);
    Route::put('offer-campaigns/{campaign}', [OfferCampaignController::class, 'update']);
    Route::delete('offer-campaigns/{campaign}', [OfferCampaignController::class, 'destroy']);

    // subscribers (list + remove)
    Route::get('subscribers', [SubscriberController::class, 'index']);
    Route::delete('subscribers/{subscriber}', [SubscriberController::class, 'destroy']);
});
