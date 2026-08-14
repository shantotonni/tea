<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\BlendQuestion;
use App\Models\CollectionNote;
use App\Models\CreationTile;
use App\Models\FooterLink;
use App\Models\InstaShot;
use App\Models\PromoBanner;
use App\Models\Founder;
use App\Models\FounderPoint;
use App\Models\HeroFeature;
use App\Models\HeroSlide;
use App\Models\HeroStat;
use App\Models\MarqueeItem;
use App\Models\NavLink;
use App\Models\OfferCampaign;
use App\Models\ProcessStep;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\SocialLink;
use App\Models\StoryPoint;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Public storefront API — no auth. Serves the Nuxt frontend.
 * Shapes DB rows into exactly what the storefront components expect.
 */
class PublicController extends Controller
{
    public function homepage()
    {
        try {
            $data = Cache::remember('cha_public_homepage_v1', 120, function () {
                $s = Setting::grouped();

                // Hero
                $hero = [
                    'copy' => $s['hero'] ?? [],
                    'slides' => HeroSlide::where('is_published', true)->orderBy('sort_order')->orderBy('id')->pluck('image'),
                    'features' => HeroFeature::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                        ->get()->map(fn ($f) => ['icon' => $f->icon, 'label' => $f->label]),
                    'stats' => HeroStat::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                        ->get()->map(fn ($st) => ['value' => $st->value, 'label' => $st->label]),
                ];

                // Marquee
                $marquee = MarqueeItem::where('is_published', true)->orderBy('sort_order')->orderBy('id')->pluck('label');

                // Offer Campaign
                $campaign = OfferCampaign::live()
                    ->orderBy('sort_order')->orderByDesc('id')
                    ->with(['promoCode', 'products' => fn ($q) => $q->where('status', '!=', 'Out of stock')])
                    ->first();

                $offer = null;
                if ($campaign && $campaign->products->isNotEmpty()) {
                    $coupon = null;
                    $promo = $campaign->promoCode;
                    if ($promo && $promo->is_active) {
                        $now = now();
                        $started = ! $promo->starts_at || $now->gte($promo->starts_at);
                        $notExpired = ! $promo->expires_at || $now->lte($promo->expires_at);
                        $hasUses = $promo->usage_limit === null || $promo->used_count < $promo->usage_limit;
                        if ($started && $notExpired && $hasUses) {
                            $coupon = [
                                'code' => $promo->code,
                                'label' => $promo->type === 'percent'
                                    ? $promo->value.'% OFF'
                                    : '৳'.number_format($promo->value).' OFF',
                                'description' => $promo->description,
                                'free_shipping' => (bool) $promo->free_shipping,
                                'min_subtotal' => $promo->min_subtotal ? (int) $promo->min_subtotal : null,
                            ];
                        }
                    }
                    $offer = [
                        'title' => $campaign->title,
                        'subtitle' => $campaign->subtitle,
                        'badge' => $campaign->badge,
                        'discount_label' => $campaign->discount_label,
                        'accent' => $campaign->accent,
                        'ends_at' => optional($campaign->ends_at)->toIso8601String(),
                        'coupon' => $coupon,
                        'products' => $campaign->products->map(fn ($p) => $this->shape($p))->values(),
                    ];
                }

                // Story
                $story = [
                    'copy' => $s['story'] ?? [],
                    'points' => StoryPoint::where('is_published', true)->orderBy('sort_order')->orderBy('id')->pluck('text'),
                    'images' => [
                        'main' => '/images/garden.jpg',
                        'float' => '/images/leaves.jpg',
                    ],
                ];

                // Sections
                $sections = [
                    'collection' => [
                        'copy' => $s['collection'] ?? [],
                        'notes' => CollectionNote::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                            ->get()->map(fn ($n) => ['icon' => $n->icon, 'label' => $n->label]),
                    ],
                    'creations' => [
                        'copy' => $s['creations'] ?? [],
                        'tiles' => CreationTile::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                            ->get()->map(fn ($t) => [
                                'image' => $t->image, 'label' => $t->label, 'meta' => $t->meta,
                                'target' => $t->target, 'is_wide' => (bool) $t->is_wide,
                            ]),
                    ],
                    'promos' => PromoBanner::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                        ->get()->map(fn ($p) => [
                            'image' => $p->image, 'badge' => $p->badge, 'eyebrow' => $p->eyebrow,
                            'title' => $p->title, 'text' => $p->text, 'target' => $p->target, 'cta' => $p->cta,
                        ]),
                    'insta' => [
                        'copy' => $s['insta'] ?? [],
                        'shots' => InstaShot::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                            ->get()->map(fn ($sh) => ['image' => $sh->image, 'caption' => $sh->caption, 'likes' => $sh->likes]),
                    ],
                    'newsletter' => $s['newsletter'] ?? [],
                    'giftbox' => $s['giftbox'] ?? [],
                ];

                // Process
                $process = [
                    'data' => ProcessStep::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                        ->get()->map(fn ($pstep) => ['num' => $pstep->num, 'title' => $pstep->title, 'text' => $pstep->text]),
                    'images' => [
                        'bg' => '/images/process-bg.jpg',
                    ],
                ];

                // Founder
                $founders = Founder::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                    ->get()->map(fn ($f) => [
                        'name' => $f->name,
                        'role' => $f->role,
                        'initials' => $f->initials ?: strtoupper(substr($f->name, 0, 2)),
                    ]);
                $fpoints = FounderPoint::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                    ->get()->map(fn ($fp) => ['num' => $fp->num, 'title' => $fp->title, 'text' => $fp->text]);
                $founder = [
                    'copy' => $s['founder'] ?? [],
                    'founders' => $founders,
                    'points' => $fpoints,
                    'images' => [
                        'founder' => '/images/founder.jpg',
                        'craft' => '/images/craft.jpg',
                    ],
                ];

                // Quotes
                $quoteRows = Quote::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                    ->get()->map(fn ($q) => [
                        'tab' => $q->tab,
                        'text' => $q->text,
                        'author' => $q->author,
                        'title' => $q->title,
                    ]);
                $quotes = [
                    'wisdom' => $quoteRows->where('tab', 'wisdom')->values(),
                    'health' => $quoteRows->where('tab', 'health')->values(),
                    'images' => [
                        'bg' => '/images/quote-bg.jpg',
                        'badge' => '/images/pouch-gold.jpeg',
                    ],
                ];

                // Reviews
                $reviews = Review::where('is_published', true)->orderBy('sort_order')->orderByDesc('id')
                    ->get()->map(fn ($r) => [
                        'lang' => $r->lang,
                        'text' => $r->text,
                        'name' => $r->name,
                        'city' => $r->city,
                        'product' => $r->product,
                        'avatar' => $r->avatar,
                        'rating' => (int) $r->rating,
                        'verified' => (bool) $r->verified,
                        'date' => optional($r->created_at)->format('F Y'),
                    ]);

                // Posts
                $posts = BlogPost::where('is_published', true)->orderBy('sort_order')->orderByDesc('published_at')
                    ->get()->map(fn ($bp) => [
                        'id' => $bp->id,
                        'category' => $bp->category,
                        'catLabel' => $bp->cat_label,
                        'title' => $bp->title,
                        'titleBn' => $bp->title_bn,
                        'excerpt' => $bp->excerpt,
                        'image' => $bp->image,
                        'author' => $bp->author,
                        'role' => $bp->role,
                        'date' => optional($bp->published_at)->format('F j, Y'),
                        'readTime' => $bp->read_time,
                        'featured' => (bool) $bp->is_featured,
                    ]);

                // FAQs
                $faqs = Faq::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                    ->get()->map(fn ($fq) => ['q' => $fq->question, 'a' => $fq->answer]);

                // Blend Finder
                $questions = BlendQuestion::with('options')
                    ->where('is_published', true)
                    ->orderBy('sort_order')->orderBy('id')
                    ->get()
                    ->map(fn ($bq) => [
                        'key' => $bq->key,
                        'label' => $bq->label,
                        'options' => $bq->options->map(fn ($o) => [
                            'id' => $o->opt_id,
                            'title' => $o->title,
                            'hint' => $o->hint,
                            'icon' => $o->icon,
                        ]),
                    ]);
                $defaultReasons = [
                    'masala-chai' => 'You want spice — this is our warming blend, built for adha-elach cha.',
                    'royal-black' => 'Milk and strength need body. This malty black holds up to both.',
                    'kunjo-signature-gold' => 'You want depth without harshness — our golden-tip house reserve.',
                    'silver-white' => 'Light and late means delicate. Silver White is our softest, lowest-caffeine cup.',
                    'highland-green' => 'Bright and clean — the morning green that wakes you without a jolt.',
                    'kunjo-classic-green' => 'Smooth, even, endlessly drinkable. The one you finish fastest.',
                ];
                $blendFinder = [
                    'questions' => $questions,
                    'reasons' => array_merge($defaultReasons, $s['blend_reasons'] ?? []),
                    'images' => [
                        'bg' => '/images/finder-bg.svg',
                    ],
                ];

                return [
                    'hero' => $hero,
                    'marquee' => $marquee,
                    'offer' => $offer,
                    'story' => $story,
                    'sections' => $sections,
                    'process' => $process,
                    'founder' => $founder,
                    'quotes' => $quotes,
                    'reviews' => $reviews,
                    'posts' => $posts,
                    'faqs' => $faqs,
                    'blendFinder' => $blendFinder,
                ];
            });

            return response()->json(['data' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to load homepage data', 'error' => $e->getMessage()], 500);
        }
    }
    public function products()
    {
        $products = Product::where('status', '!=', 'Out of stock')
            ->orderBy('id')
            ->get()
            ->map(fn ($p) => $this->shape($p));

        return response()->json(['data' => $products]);
    }

    public function reviews()
    {
        $reviews = Review::where('is_published', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($r) => [
                'lang' => $r->lang,
                'text' => $r->text,
                'name' => $r->name,
                'city' => $r->city,
                'product' => $r->product,
                'avatar' => $r->avatar,
                'rating' => (int) $r->rating,
                'verified' => (bool) $r->verified,
                'date' => optional($r->created_at)->format('F Y'),
            ]);

        return response()->json(['data' => $reviews]);
    }

    public function faqs()
    {
        $faqs = Faq::where('is_published', true)
            ->orderBy('sort_order')->orderBy('id')
            ->get()
            ->map(fn ($f) => ['q' => $f->question, 'a' => $f->answer]);

        return response()->json(['data' => $faqs]);
    }

    public function posts()
    {
        $posts = BlogPost::where('is_published', true)
            ->orderBy('sort_order')->orderByDesc('published_at')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'category' => $p->category,
                'catLabel' => $p->cat_label,
                'title' => $p->title,
                'titleBn' => $p->title_bn,
                'excerpt' => $p->excerpt,
                'image' => $p->image,
                'author' => $p->author,
                'role' => $p->role,
                'date' => optional($p->published_at)->format('F j, Y'),
                'readTime' => $p->read_time,
                'featured' => (bool) $p->is_featured,
            ]);

        return response()->json(['data' => $posts]);
    }

    public function post($id)
    {
        $post = BlogPost::where('is_published', true)->where('id', $id)->first();
        if (! $post) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $post->id,
                'category' => $post->category,
                'catLabel' => $post->cat_label,
                'title' => $post->title,
                'titleBn' => $post->title_bn,
                'excerpt' => $post->excerpt,
                'content' => $post->content ?? $post->excerpt,
                'image' => $post->image,
                'author' => $post->author,
                'role' => $post->role,
                'date' => optional($post->published_at)->format('F j, Y'),
                'readTime' => $post->read_time,
                'featured' => (bool) $post->is_featured,
            ]
        ]);
    }

    public function process()
    {
        $steps = ProcessStep::where('is_published', true)
            ->orderBy('sort_order')->orderBy('id')
            ->get()
            ->map(fn ($s) => ['num' => $s->num, 'title' => $s->title, 'text' => $s->text]);

        return response()->json([
            'data' => $steps,
            'images' => [
                'bg' => '/images/process-bg.jpg',
            ]
        ]);
    }

    public function quotes()
    {
        // group into { wisdom: [...], health: [...] } — exactly the storefront shape
        $rows = Quote::where('is_published', true)
            ->orderBy('sort_order')->orderBy('id')
            ->get()
            ->map(fn ($q) => [
                'tab' => $q->tab,
                'text' => $q->text,
                'author' => $q->author,
                'title' => $q->title,
            ]);

        return response()->json(['data' => [
            'wisdom' => $rows->where('tab', 'wisdom')->values(),
            'health' => $rows->where('tab', 'health')->values(),
            'images' => [
                'bg' => '/images/quote-bg.jpg',
                'badge' => '/images/pouch-gold.jpeg',
            ],
        ]]);
    }

    public function founder()
    {
        $copy = Setting::grouped()['founder'] ?? [];

        $founders = Founder::where('is_published', true)
            ->orderBy('sort_order')->orderBy('id')
            ->get()
            ->map(fn ($f) => [
                'name' => $f->name,
                'role' => $f->role,
                'initials' => $f->initials ?: strtoupper(substr($f->name, 0, 2)),
            ]);

        $points = FounderPoint::where('is_published', true)
            ->orderBy('sort_order')->orderBy('id')
            ->get()
            ->map(fn ($p) => ['num' => $p->num, 'title' => $p->title, 'text' => $p->text]);

        return response()->json(['data' => [
            'copy' => $copy,
            'founders' => $founders,
            'points' => $points,
            'images' => [
                'founder' => '/images/founder.jpg',
                'craft' => '/images/craft.jpg',
            ],
        ]]);
    }

    public function hero()
    {
        $copy = Setting::grouped()['hero'] ?? [];

        return response()->json(['data' => [
            'copy' => $copy,
            'slides' => HeroSlide::where('is_published', true)->orderBy('sort_order')->orderBy('id')->pluck('image'),
            'features' => HeroFeature::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                ->get()->map(fn ($f) => ['icon' => $f->icon, 'label' => $f->label]),
            'stats' => HeroStat::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                ->get()->map(fn ($s) => ['value' => $s->value, 'label' => $s->label]),
        ]]);
    }

    public function marquee()
    {
        return response()->json([
            'data' => MarqueeItem::where('is_published', true)->orderBy('sort_order')->orderBy('id')->pluck('label'),
        ]);
    }

    public function story()
    {
        $copy = Setting::grouped()['story'] ?? [];

        return response()->json(['data' => [
            'copy' => $copy,
            'points' => StoryPoint::where('is_published', true)->orderBy('sort_order')->orderBy('id')->pluck('text'),
            'images' => [
                'main' => '/images/garden.jpg',
                'float' => '/images/leaves.jpg',
            ],
        ]]);
    }

    public function blendFinder()
    {
        $questions = BlendQuestion::with('options')
            ->where('is_published', true)
            ->orderBy('sort_order')->orderBy('id')
            ->get()
            ->map(fn ($q) => [
                'key' => $q->key,
                'label' => $q->label,
                'options' => $q->options->map(fn ($o) => [
                    'id' => $o->opt_id,
                    'title' => $o->title,
                    'hint' => $o->hint,
                    'icon' => $o->icon,
                ]),
            ]);

        $defaultReasons = [
            'masala-chai' => 'You want spice — this is our warming blend, built for adha-elach cha.',
            'royal-black' => 'Milk and strength need body. This malty black holds up to both.',
            'kunjo-signature-gold' => 'You want depth without harshness — our golden-tip house reserve.',
            'silver-white' => 'Light and late means delicate. Silver White is our softest, lowest-caffeine cup.',
            'highland-green' => 'Bright and clean — the morning green that wakes you without a jolt.',
            'kunjo-classic-green' => 'Smooth, even, endlessly drinkable. The one you finish fastest.',
        ];

        $reasons = array_merge($defaultReasons, Setting::grouped()['blend_reasons'] ?? []);

        return response()->json([
            'data' => [
                'questions' => $questions,
                'reasons' => $reasons,
                'images' => [
                    'bg' => '/images/finder-bg.svg',
                ],
            ]
        ]);
    }

    public function navbar()
    {
        $store = Setting::grouped()['store'] ?? [];

        return response()->json(['data' => [
            'brand' => $store['name'] ?? 'Cha Kunjo',
            'logo' => $store['logo'] ?? '',
            'links' => NavLink::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                ->get()->map(fn ($l) => ['label' => $l->label, 'target' => $l->target, 'is_cta' => (bool) $l->is_cta]),
        ]]);
    }

    public function footerData()
    {
        $copy = Setting::grouped()['footer'] ?? [];
        $store = Setting::grouped()['store'] ?? [];

        $links = FooterLink::where('is_published', true)->orderBy('sort_order')->orderBy('id')->get();

        // contact block comes straight from Store Profile so editing it in
        // Settings → Store Profile updates the storefront footer live.
        $contactLinks = collect();
        if (! empty($store['address'])) {
            $contactLinks->push(['label' => $store['address'], 'target' => null]);
        }
        if (! empty($store['email'])) {
            $contactLinks->push(['label' => $store['email'], 'target' => 'mailto:'.$store['email']]);
        }
        if (! empty($store['phone'])) {
            $contactLinks->push(['label' => $store['phone'], 'target' => 'tel:'.str_replace(' ', '', $store['phone'])]);
        }
        // any extra manually-added contact links still appended
        $contactLinks = $contactLinks->concat(
            $links->where('col', 'contact')->map(fn ($l) => ['label' => $l->label, 'target' => $l->target])->values()
        )->values();

        return response()->json(['data' => [
            'brand' => $store['name'] ?? 'Cha Kunjo',
            'logo' => $store['logo'] ?? '',
            // Store Profile description drives the footer blurb; footer.about is the fallback
            'about' => ! empty($store['description']) ? $store['description'] : ($copy['about'] ?? ''),
            'copyright' => $copy['copyright'] ?? '',
            'bottom_note' => $copy['bottom_note'] ?? '',
            'explore' => $links->where('col', 'explore')->map(fn ($l) => ['label' => $l->label, 'target' => $l->target])->values(),
            'support' => $links->where('col', 'support')->map(fn ($l) => ['label' => $l->label, 'target' => $l->target])->values(),
            'contact' => $contactLinks,
            'socials' => SocialLink::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                ->get()->map(fn ($s) => ['name' => $s->name, 'href' => $s->href]),
        ]]);
    }

    public function seo()
    {
        return response()->json(['data' => Setting::grouped()['seo'] ?? []]);
    }

    public function districts()
    {
        $rows = \App\Models\District::where('is_active', true)
            ->orderBy('division')->orderBy('name')
            ->get(['name', 'division']);

        return response()->json(['data' => $rows]);
    }

    public function settings()
    {
        $s = Setting::grouped();

        return response()->json(['data' => [
            'store' => [
                'name' => $s['store']['name'] ?? 'Cha Kunjo',
                'email' => $s['store']['email'] ?? 'hello@chakunjo.com',
                'phone' => $s['store']['phone'] ?? '+880 1712-345678',
                'currency' => $s['store']['currency'] ?? 'BDT — Bangladeshi Taka (৳)',
                'currency_symbol' => str_contains($s['store']['currency'] ?? '', 'USD') ? '$' : '৳',
                'address' => $s['store']['address'] ?? 'Sreemangal, Sylhet, Bangladesh',
                'description' => $s['store']['description'] ?? 'Hand-plucked single-origin tea from the misty hills of Sreemangal.',
            ],
            'shipping' => [
                'inside_dhaka' => (int) ($s['shipping']['inside_dhaka'] ?? 60),
                'outside_dhaka' => (int) ($s['shipping']['outside_dhaka'] ?? 120),
                'free_above' => (int) ($s['shipping']['free_above'] ?? 2000),
                'courier' => $s['shipping']['courier'] ?? 'Steadfast',
                'note' => $s['shipping']['note'] ?? '',
            ],
            'payments' => [
                'bkash' => [
                    'enabled' => (bool) ($s['payments']['bkash_enabled'] ?? true),
                    'mode' => $s['payments']['bkash_mode'] ?? 'Sandbox',
                    'number' => $s['payments']['bkash_number'] ?? '01700-000000',
                ],
                'nagad' => [
                    'enabled' => (bool) ($s['payments']['nagad_enabled'] ?? true),
                    'mode' => $s['payments']['nagad_mode'] ?? 'Sandbox',
                    'number' => $s['payments']['nagad_number'] ?? '01800-000000',
                ],
                'cod' => [
                    'enabled' => (bool) ($s['payments']['cod_enabled'] ?? true),
                ],
            ],
            'ai' => [
                'enabled' => (bool) ($s['ai']['enabled'] ?? true),
                'provider' => $s['ai']['provider'] ?? 'OpenAI',
                'model' => $s['ai']['model'] ?? 'gpt-4o-mini',
                'auto_blurb' => (bool) ($s['ai']['auto_generate_blurb'] ?? true),
                'recommendation_assistant' => (bool) ($s['ai']['recommendation_assistant'] ?? true),
            ],
        ]]);
    }

    public function shipping()
    {
        $s = Setting::grouped()['shipping'] ?? [];

        return response()->json(['data' => [
            'inside_dhaka' => (int) ($s['inside_dhaka'] ?? 60),
            'outside_dhaka' => (int) ($s['outside_dhaka'] ?? 120),
            'free_above' => (int) ($s['free_above'] ?? 2000),
            'note' => $s['note'] ?? '',
        ]]);
    }

    /**
     * All homepage "section" content in one payload. The storefront components
     * share a single useAsyncData('sections') call, so this is fetched once.
     */
    public function sections()
    {
        $s = Setting::grouped();

        return response()->json(['data' => [
            'collection' => [
                'copy' => $s['collection'] ?? [],
                'notes' => CollectionNote::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                    ->get()->map(fn ($n) => ['icon' => $n->icon, 'label' => $n->label]),
            ],
            'creations' => [
                'copy' => $s['creations'] ?? [],
                'tiles' => CreationTile::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                    ->get()->map(fn ($t) => [
                        'image' => $t->image, 'label' => $t->label, 'meta' => $t->meta,
                        'target' => $t->target, 'is_wide' => (bool) $t->is_wide,
                    ]),
            ],
            'promos' => PromoBanner::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                ->get()->map(fn ($p) => [
                    'image' => $p->image, 'badge' => $p->badge, 'eyebrow' => $p->eyebrow,
                    'title' => $p->title, 'text' => $p->text, 'target' => $p->target, 'cta' => $p->cta,
                ]),
            'insta' => [
                'copy' => $s['insta'] ?? [],
                'shots' => InstaShot::where('is_published', true)->orderBy('sort_order')->orderBy('id')
                    ->get()->map(fn ($sh) => ['image' => $sh->image, 'caption' => $sh->caption, 'likes' => $sh->likes]),
            ],
            'newsletter' => $s['newsletter'] ?? [],
            'giftbox' => $s['giftbox'] ?? [],
        ]]);
    }

    /**
     * GET /api/public/offer — currently-live occasion offer campaign (or null).
     * Frontend hides the whole section when data is null.
     */
    public function offer()
    {
        $campaign = OfferCampaign::live()
            ->orderBy('sort_order')->orderByDesc('id')
            ->with(['promoCode', 'products' => fn ($q) => $q->where('status', '!=', 'Out of stock')])
            ->first();

        if (! $campaign || $campaign->products->isEmpty()) {
            return response()->json(['data' => null]);
        }

        // surface the coupon only when it is currently redeemable
        $coupon = null;
        $promo = $campaign->promoCode;
        if ($promo && $promo->is_active) {
            $now = now();
            $started = ! $promo->starts_at || $now->gte($promo->starts_at);
            $notExpired = ! $promo->expires_at || $now->lte($promo->expires_at);
            $hasUses = $promo->usage_limit === null || $promo->used_count < $promo->usage_limit;
            if ($started && $notExpired && $hasUses) {
                $coupon = [
                    'code' => $promo->code,
                    'label' => $promo->type === 'percent'
                        ? $promo->value.'% OFF'
                        : '৳'.number_format($promo->value).' OFF',
                    'description' => $promo->description,
                    'free_shipping' => (bool) $promo->free_shipping,
                    'min_subtotal' => $promo->min_subtotal ? (int) $promo->min_subtotal : null,
                ];
            }
        }

        return response()->json(['data' => [
            'title' => $campaign->title,
            'subtitle' => $campaign->subtitle,
            'badge' => $campaign->badge,
            'discount_label' => $campaign->discount_label,
            'accent' => $campaign->accent,
            'ends_at' => optional($campaign->ends_at)->toIso8601String(),
            'coupon' => $coupon,
            'products' => $campaign->products->map(fn ($p) => $this->shape($p))->values(),
        ]]);
    }

    public function product(string $slug)
    {
        $product = Product::where('slug', $slug)->first();
        if (! $product) {
            return response()->json(['message' => 'Blend not found'], 404);
        }

        return response()->json(['data' => $this->shape($product)]);
    }

    /**
     * POST /api/public/ai/chat — Grounded AI Concierge Chatbot
     */
    public function aiChat(Request $request)
    {
        try {
            $userMsg = trim($request->input('message', ''));
            if (empty($userMsg)) {
                return response()->json(['text' => 'দয়া করে একটি প্রশ্ন লিখুন।', 'products' => []]);
            }

            $ai = Setting::grouped()['ai'] ?? [];
            $apiKey = trim($ai['api_key'] ?? '');

            // Fetch DB products for grounding & smart matching
            $products = Product::where('status', '!=', 'Out of stock')->get();
            $catalogSummary = $products->map(function ($p) {
                $d = $p->details ?? [];
                $tasting = is_array($d['tasting'] ?? null) ? json_encode($d['tasting']) : '';
                // facts + specs carry caffeine / origin / brewing so the AI can answer detail questions
                $facts = is_array($d['facts'] ?? null) ? implode(', ', array_map(fn ($k, $v) => "$k: $v", array_keys($d['facts']), $d['facts'])) : '';
                $specs = '';
                if (is_array($d['specs'] ?? null)) {
                    $specs = implode(', ', array_map(fn ($s) => ($s['k'] ?? '').': '.($s['v'] ?? ''), $d['specs']));
                }
                return "- [{$p->slug}] {$p->name} ({$p->category}) — ৳{$p->price}, {$p->weight}. {$p->blurb} "
                    ."Tasting: {$tasting}. Facts: {$facts}. Specs: {$specs}";
            })->implode("\n");

            $faqs = Faq::where('is_published', true)->get()->map(fn ($f) => "Q: {$f->question} A: {$f->answer}")->implode("\n");

            // shipping + payment context so info questions get real answers
            $store = Setting::grouped()['store'] ?? [];
            $ship = Setting::grouped()['shipping'] ?? [];
            $storeInfo = "Delivery: inside Dhaka ৳".($ship['inside_dhaka'] ?? 60).", outside Dhaka ৳".($ship['outside_dhaka'] ?? 120).", free above ৳".($ship['free_above'] ?? 2000).". "
                ."Payment: Cash on Delivery, bKash, Nagad. Contact: ".($store['phone'] ?? '')." ".($store['email'] ?? '').". Location: ".($store['address'] ?? 'Sreemangal');

            // recent conversation for contextual follow-ups
            $history = '';
            foreach ((array) $request->input('history', []) as $h) {
                $who = ($h['sender'] ?? '') === 'user' ? 'User' : 'Assistant';
                $line = trim((string) ($h['text'] ?? ''));
                if ($line !== '') {
                    $history .= "{$who}: ".mb_substr($line, 0, 300)."\n";
                }
            }

            $systemPrompt = "You are 'Cha Kunjo Concierge', an expert, luxury Bangladeshi tea concierge assistant.
DIRECTLY answer the user's specific question first — do not open with a generic welcome unless they only greeted you.
Reply in the SAME language the user wrote in (Bangla → reply fully in Bangla; English → fully in English — never mix).
Keep replies warm, polite and concise (2-4 sentences).
Answer ONLY from the Cha Kunjo catalog, FAQs and store info below. If a detail truly isn't there, say so briefly and offer a close alternative. Never invent facts or external brands.
Only when RECOMMENDING a specific tea, put its exact slug in square brackets like [kunjo-classic-green] (max 2). For general info (delivery, payment, hours) do NOT bracket any product.

CATALOG:
{$catalogSummary}

STORE INFO:
{$storeInfo}

FAQS:
{$faqs}
".($history ? "\nCONVERSATION SO FAR:\n{$history}" : '')."
User: {$userMsg}
Assistant:";

            $text = '';
            if (!empty($apiKey)) {
                $text = $this->callGemini($apiKey, $systemPrompt);
            }

            $recommendedProducts = [];

            // Smart fallback if Gemini API is busy or key not set
            if (empty($text)) {
                $lowerMsg = strtolower($userMsg);

                if (str_contains($lowerMsg, 'ঘুম') || str_contains($lowerMsg, 'sleep') || str_contains($lowerMsg, 'রাত')) {
                    $text = "রাতের শান্ত ঘুম ও রিফ্রেশমেন্টের জন্য আমাদের ক্যামোমাইল বা সিলভার হোয়াইট টি খুবই চমৎকার। এটি ক্যাফেইন মুক্ত এবং মানসিক ক্লান্তি দূর করে।";
                    $matched = $products->filter(fn($p) => str_contains(strtolower($p->name), 'white') || str_contains(strtolower($p->category), 'white'));
                } elseif (str_contains($lowerMsg, 'গিফট') || str_contains($lowerMsg, 'gift') || str_contains($lowerMsg, 'মা') || str_contains($lowerMsg, 'উপহার')) {
                    $text = "প্রিয়জনের জন্য আমাদের বিশেষ সুগন্ধি অর্গানিক টি গিফট বক্স খুবই আকর্ষণীয় উপহার। এতে প্রিমিয়াম ব্লেন্ডের চমৎকার চায়ের সম্ভার রয়েছে।";
                    $matched = $products->filter(fn($p) => $p->in_gift_box || str_contains(strtolower($p->name), 'royal') || str_contains(strtolower($p->name), 'gold'));
                } elseif (str_contains($lowerMsg, 'মসলা') || str_contains($lowerMsg, 'masala') || str_contains($lowerMsg, 'spice')) {
                    $text = "খাটি এলাচ, লবঙ্গ ও দারুচিনির মেলবন্ধনে তৈরি আমাদের কুঞ্জ সিগনেচার মসলা চা খুব জনপ্রিয়। দুধ দিয়ে বা লিকার বানিয়ে দারুণ উপভোগ করা যায়।";
                    $matched = $products->filter(fn($p) => str_contains(strtolower($p->name), 'masala') || str_contains(strtolower($p->category), 'spice'));
                } else {
                    $text = "আমাদের চায়ের কালেকশনে আপনাকে স্বাগতম! শ্রীমঙ্গলের বাগান থেকে সংগৃহীত আমাদের অর্গানিক ব্লেন্ডসমূহ লিকার, দুধ চা কিংবা স্বাস্থ্য সুরক্ষায় অত্যন্ত চমৎকার।";
                    $matched = $products->take(2);
                }

                foreach ($matched->take(2) as $p) {
                    $recommendedProducts[] = $this->shape($p);
                }
            } else {
                // only surface cards the AI actually recommended — bracketed slug, or the
                // product's exact name appearing in the REPLY (not the user's message).
                // no forced "first product" so info answers (delivery/payment) show no card.
                foreach ($products as $p) {
                    if (str_contains($text, "[{$p->slug}]") || stripos($text, $p->name) !== false) {
                        $recommendedProducts[] = $this->shape($p);
                    }
                }
            }

            // Clean out bracketed slugs from visible reply text
            $cleanText = preg_replace('/\[[a-z0-9\-]+\]/', '', $text);

            return response()->json([
                'text' => trim($cleanText),
                'products' => array_slice($recommendedProducts, 0, 2),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'text' => 'চা কুঞ্জ কালেকশন থেকে আপনার প্রশ্নটির উত্তর দিতে পেরে আনন্দিত। আমাদের যেকোনো অর্গানিক চা সরাসরি ব্রাউজ বা কার্ট করতে পারেন।',
                'products' => [],
            ]);
        }
    }

    /**
     * POST /api/public/ai/pdp-ask — Product specific Q&A
     */
    public function aiPdpQuestion(Request $request)
    {
        $request->validate([
            'product_slug' => 'required|string',
            'question' => 'required|string|max:500',
        ]);

        $product = Product::where('slug', $request->input('product_slug'))->first();
        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $ai = Setting::grouped()['ai'] ?? [];
        $apiKey = trim($ai['api_key'] ?? '');

        $details = $product->details ?? [];
        $factsJson = json_encode($details['facts'] ?? []);
        $tastingJson = json_encode($details['tasting'] ?? []);
        $brewingJson = json_encode($details['brewing'] ?? []);

        $prompt = "You are an expert tea sommelier for Cha Kunjo.
Answer the customer's question about the tea '{$product->name}' ({$product->category}, ৳{$product->price}).
Tea Facts: {$factsJson}
Tasting Profile: {$tastingJson}
Brewing Instructions: {$brewingJson}
Blurb: {$product->blurb}

Customer Question: {$request->input('question')}

Answer in 1-2 friendly, accurate sentences in Bengali/English matching the customer's language.";

        $answer = $this->callGemini($apiKey, $prompt);

        if (empty($answer)) {
            $answer = "এই চা-টি একটি প্রিমিয়াম অর্গানিক ব্লেন্ড। এটি " . ($product->category) . " ক্যাটাগরির অন্তর্ভুক্ত এবং এর টেস্ট নোট খুব রিফ্রেশিং।";
        }

        return response()->json(['answer' => trim($answer)]);
    }

    /**
     * POST /api/public/ai/recommend — Natural language blend recommendation
     */
    public function aiBlendRecommend(Request $request)
    {
        $request->validate(['prompt' => 'required|string|max:500']);
        $promptText = trim($request->input('prompt'));

        $ai = Setting::grouped()['ai'] ?? [];
        $apiKey = trim($ai['api_key'] ?? '');

        $products = Product::where('status', '!=', 'Out of stock')->get();
        $catalogSummary = $products->map(fn ($p) => "- [{$p->slug}] {$p->name} ({$p->category}) - {$p->blurb}")->implode("\n");

        $sysPrompt = "User wants tea based on this description: '{$promptText}'
Match 1 or 2 best teas from Cha Kunjo catalog:
{$catalogSummary}
Explain why in 2 engaging sentences. Mention product slugs in brackets [slug].";

        $reply = $this->callGemini($apiKey, $sysPrompt);

        $matchedProducts = [];
        foreach ($products as $p) {
            if (str_contains($reply, "[{$p->slug}]")) {
                $matchedProducts[] = $this->shape($p);
            }
        }

        if (empty($matchedProducts) && $products->count() > 0) {
            $matchedProducts[] = $this->shape($products->first());
        }

        $cleanReply = preg_replace('/\[[a-z0-9\-]+\]/', '', $reply);

        return response()->json([
            'explanation' => trim($cleanReply),
            'products' => array_slice($matchedProducts, 0, 2),
        ]);
    }

    /**
     * POST /api/public/ai/gift-note — AI handwritten gift message generator
     */
    public function aiGiftNote(Request $request)
    {
        $recipient = $request->input('recipient', 'Friend');
        $occasion = $request->input('occasion', 'Birthday');

        $ai = Setting::grouped()['ai'] ?? [];
        $apiKey = trim($ai['api_key'] ?? '');

        $prompt = "Write a heartwarming, 2-sentence handwritten gift note for {$recipient} on the occasion of {$occasion}, accompanying a box of luxury organic tea from Cha Kunjo. Return only the note text.";

        $note = $this->callGemini($apiKey, $prompt);

        if (empty($note)) {
            $note = "Wishing you moments of pure warmth, peace, and delight with every soothing sip of this luxury tea blend. Enjoy!";
        }

        return response()->json(['note' => trim($note)]);
    }

    /** Helper to call Google Gemini API with fallback models */
    private function callGemini(string $apiKey, string $prompt): string
    {
        if (empty($apiKey)) {
            return '';
        }

        // current models — the older gemini-1.5-* / 2.0-flash names now 404 ("no longer available")
        $models = ['gemini-2.5-flash', 'gemini-flash-latest', 'gemini-2.5-pro'];

        foreach ($models as $m) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$m}:generateContent?key={$apiKey}";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'contents' => [['parts' => [['text' => $prompt]]]]
            ]));
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            $resText = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $resJson = json_decode($resText, true);
                $text = $resJson['candidates'][0]['content']['parts'][0]['text'] ?? '';
                if (!empty($text)) {
                    return $text;
                }
            }
        }

        return '';
    }

    /**
     * GET /api/public/recent-orders — Dynamic Live Orders Feed with Real Product IDs
     */
    public function recentOrders()
    {
        try {
            $orders = Order::with('items.product')->latest()->take(10)->get();
            $products = Product::where('status', '!=', 'Out of stock')->get();
            $feed = [];

            foreach ($orders as $order) {
                $name = trim($order->customer_name ?: $order->name ?: 'Customer');
                $nameParts = explode(' ', $name);
                $firstName = $nameParts[0] ?? 'Customer';
                $maskedName = strlen($firstName) > 2 ? substr($firstName, 0, 3) . '***' : $firstName;

                $item = $order->items->first();
                if ($item) {
                    $prod = $item->product ?: $products->firstWhere('name', $item->product_name) ?: $products->first();
                    if ($prod) {
                        $shaped = $this->shape($prod);
                        $feed[] = [
                            'id' => $order->id,
                            'order_number' => $order->code ?: "CK-{$order->id}",
                            'customer' => $maskedName,
                            'product_id' => $prod->id,
                            'product_name' => $prod->name,
                            'product_slug' => $prod->slug,
                            'product_image' => $shaped['image'],
                            'price' => $item->price ?: $prod->price,
                            'qty' => $item->qty ?: 1,
                            'created_at' => $order->created_at ? $order->created_at->diffForHumans() : 'Recently',
                        ];
                    }
                }
            }

            // Always guarantee a rich feed by populating with real DB products when order count is low
            if (count($feed) < 6 && $products->count() > 0) {
                $mockNames = ['Sabbir H.', 'Tanvir K.', 'Farhana R.', 'Nusrat A.', 'Mahmud S.', 'Anik R.'];
                $times = ['2 mins ago', '7 mins ago', '15 mins ago', '28 mins ago', '42 mins ago', '1 hour ago'];

                foreach ($products as $idx => $p) {
                    if (count($feed) >= 8) break;
                    $shaped = $this->shape($p);
                    $feed[] = [
                        'id' => 950 + $idx,
                        'order_number' => 'CK-' . rand(8000, 9999),
                        'customer' => $mockNames[$idx % count($mockNames)],
                        'product_id' => $p->id,
                        'product_name' => $p->name,
                        'product_slug' => $p->slug,
                        'product_image' => $shaped['image'],
                        'price' => $p->price,
                        'qty' => 1,
                        'created_at' => $times[$idx % count($times)],
                    ];
                }
            }

            return response()->json(['data' => $feed]);
        } catch (\Throwable $e) {
            return response()->json(['data' => []]);
        }
    }

    private const CAT_EMOJI = [
        'Green Tea' => '🍃', 'Black Tea' => '🍂', 'White Tea' => '🤍',
        'House Reserve' => '✿', 'Classic Blend' => '🍃', 'Spiced Blend' => '🌶', 'Oolong' => '🍵',
    ];

    /** DB row → storefront Product shape (id = slug, camelCase, leading-slash image) */
    private function shape(Product $p): array
    {
        $emoji = self::CAT_EMOJI[$p->category] ?? '';
        $details = $p->details ?? [];

        return [
            'id' => $p->slug,
            'name' => $p->name,
            'blurb' => $p->blurb,
            'image' => '/' . ltrim($p->image, '/'),
            'tag' => $p->tag,
            'tagClass' => in_array($p->tag, ['Premium', 'Signature']) ? 'gold' : '',
            'rating' => (float) $p->rating,
            'reviews' => $p->reviews,
            'category' => trim("{$emoji} {$p->category}"),
            'weight' => $p->weight,
            'price' => $p->price,
            'oldPrice' => $p->old_price,
            'isFeatured' => (bool) $p->is_featured,
            'inGiftBox' => (bool) $p->in_gift_box,
            'gallery' => $details['gallery'] ?? [],
            'tasting' => $details['tasting'] ?? [],
            'facts' => $details['facts'] ?? [],
            'specs' => $details['specs'] ?? [],
            'brewing' => $details['brewing'] ?? [],
            'story' => $details['story'] ?? null,
            'brewNote' => $details['brew_note'] ?? null,
            'shipNote' => $details['ship_note'] ?? null,
            'sizes' => $details['sizes'] ?? [],
            'seo' => $details['seo'] ?? null,
        ];
    }
}
