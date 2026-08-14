<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * field definition per group: short-key => [validation rule, type]
     * new tabs (notifications, shipping…) get added here.
     */
    private const SCHEMA = [
        'store' => [
            'name' => ['required|string|max:120', 'string'],
            'logo' => ['nullable|string|max:255', 'string'],
            'email' => ['required|email|max:150', 'string'],
            'phone' => ['nullable|string|max:40', 'string'],
            'currency' => ['nullable|string|max:40', 'string'],
            'address' => ['nullable|string|max:200', 'string'],
            'description' => ['nullable|string|max:500', 'string'],
        ],
        'notifications' => [
            'notification_email' => ['nullable|email|max:150', 'string'],
            'new_order' => ['boolean', 'bool'],
            'order_status' => ['boolean', 'bool'],
            'low_stock' => ['boolean', 'bool'],
            'new_customer' => ['boolean', 'bool'],
            'daily_digest' => ['boolean', 'bool'],
            'new_review' => ['boolean', 'bool'],
        ],
        'shipping' => [
            'inside_dhaka' => ['required|integer|min:0', 'int'],
            'outside_dhaka' => ['required|integer|min:0', 'int'],
            'free_above' => ['required|integer|min:0', 'int'],
            'courier' => ['nullable|string|max:40', 'string'],
            'note' => ['nullable|string|max:200', 'string'],
        ],
        'security' => [
            'two_factor' => ['boolean', 'bool'],
            'login_alerts' => ['boolean', 'bool'],
        ],
        'ai' => [
            'enabled' => ['boolean', 'bool'],
            'provider' => ['nullable|string|max:40', 'string'],
            'api_key' => ['nullable|string|max:255', 'string'],
            'model' => ['nullable|string|max:60', 'string'],
            'auto_generate_blurb' => ['boolean', 'bool'],
            'recommendation_assistant' => ['boolean', 'bool'],
        ],
        'payments' => [
            'bkash_enabled' => ['boolean', 'bool'],
            'bkash_mode' => ['nullable|string|max:20', 'string'],
            'bkash_app_key' => ['nullable|string|max:255', 'string'],
            'bkash_app_secret' => ['nullable|string|max:255', 'string'],
            'bkash_username' => ['nullable|string|max:120', 'string'],
            'bkash_password' => ['nullable|string|max:120', 'string'],
            'bkash_number' => ['nullable|string|max:30', 'string'],

            'nagad_enabled' => ['boolean', 'bool'],
            'nagad_mode' => ['nullable|string|max:20', 'string'],
            'nagad_merchant_id' => ['nullable|string|max:120', 'string'],
            'nagad_public_key' => ['nullable|string|max:500', 'string'],
            'nagad_private_key' => ['nullable|string|max:500', 'string'],
            'nagad_number' => ['nullable|string|max:30', 'string'],

            'cod_enabled' => ['boolean', 'bool'],
        ],
        // "Behind the pouch" founder-story section copy (storefront)
        'founder' => [
            'eyebrow' => ['required|string|max:60', 'string'],
            'title' => ['required|string|max:120', 'string'],
            'quote' => ['required|string|max:600', 'string'],
            'badge' => ['nullable|string|max:60', 'string'],
        ],
        // hero banner copy (storefront)
        'hero' => [
            'eyebrow' => ['required|string|max:120', 'string'],
            'title' => ['required|string|max:160', 'string'],
            'title_accent' => ['nullable|string|max:80', 'string'],
            'subtitle' => ['required|string|max:500', 'string'],
            'cta_primary_label' => ['required|string|max:60', 'string'],
            'cta_primary_target' => ['required|string|max:60', 'string'],
            'cta_ghost_label' => ['nullable|string|max:60', 'string'],
            'cta_ghost_target' => ['nullable|string|max:60', 'string'],
        ],
        // "Our Story" section copy (storefront)
        'story' => [
            'eyebrow' => ['required|string|max:60', 'string'],
            'title' => ['required|string|max:120', 'string'],
            'body1' => ['required|string|max:600', 'string'],
            'body2' => ['nullable|string|max:600', 'string'],
            'badge_year' => ['nullable|string|max:20', 'string'],
            'cta_label' => ['nullable|string|max:60', 'string'],
        ],
        // footer copy (storefront)
        'footer' => [
            'about' => ['nullable|string|max:400', 'string'],
            'copyright' => ['required|string|max:120', 'string'],
            'bottom_note' => ['nullable|string|max:120', 'string'],
        ],
        // SEO / social meta (storefront <head>)
        'seo' => [
            'title' => ['required|string|max:180', 'string'],
            'description' => ['required|string|max:300', 'string'],
            'keywords' => ['nullable|string|max:255', 'string'],
            'og_title' => ['nullable|string|max:180', 'string'],
            'og_description' => ['nullable|string|max:300', 'string'],
            'og_image' => ['nullable|string|max:255', 'string'],
        ],
        // "Signature Collection" section copy
        'collection' => [
            'eyebrow' => ['required|string|max:60', 'string'],
            'title' => ['required|string|max:120', 'string'],
            'lead' => ['nullable|string|max:300', 'string'],
        ],
        // "Creations with purpose" collage copy
        'creations' => [
            'eyebrow' => ['required|string|max:60', 'string'],
            'title' => ['required|string|max:120', 'string'],
            'lead' => ['nullable|string|max:300', 'string'],
            'stat1_value' => ['nullable|string|max:20', 'string'],
            'stat1_label' => ['nullable|string|max:60', 'string'],
            'stat2_value' => ['nullable|string|max:20', 'string'],
            'stat2_label' => ['nullable|string|max:60', 'string'],
            'cta_label' => ['nullable|string|max:60', 'string'],
        ],
        // Instagram strip copy
        'insta' => [
            'eyebrow' => ['required|string|max:80', 'string'],
            'handle' => ['required|string|max:60', 'string'],
        ],
        // newsletter CTA copy
        'newsletter' => [
            'title' => ['required|string|max:120', 'string'],
            'lead' => ['nullable|string|max:300', 'string'],
            'button_label' => ['nullable|string|max:40', 'string'],
            'success_label' => ['nullable|string|max:40', 'string'],
            'fine' => ['nullable|string|max:160', 'string'],
        ],
        // gift / discovery box copy + pricing
        'giftbox' => [
            'eyebrow' => ['required|string|max:60', 'string'],
            'title' => ['required|string|max:120', 'string'],
            'lead' => ['nullable|string|max:400', 'string'],
            'note' => ['nullable|string|max:120', 'string'],
            'discount_pct' => ['nullable|integer|min:0|max:90', 'int'],
        ],
    ];

    /**
     * GET /api/settings — all settings grouped.
     */
    public function index()
    {
        return response()->json(['data' => Setting::grouped()]);
    }

    /**
     * PUT /api/settings/{group} — save one section.
     */
    public function update(Request $request, string $group)
    {
        if (! isset(self::SCHEMA[$group])) {
            return response()->json(['message' => 'Unknown settings group.'], 404);
        }

        $rules = [];
        foreach (self::SCHEMA[$group] as $key => [$rule]) {
            $rules[$key] = $rule;
        }
        $data = $request->validate($rules);

        foreach (self::SCHEMA[$group] as $key => [, $type]) {
            if (array_key_exists($key, $data)) {
                Setting::put($group, $key, $data[$key], $type);
            }
        }

        return response()->json([
            'message' => 'Settings saved.',
            'data' => Setting::grouped(),
        ]);
    }

    /**
     * POST /api/settings/ai/test — Test AI Provider API Key Connection
     */
    public function testAiConnection(Request $request)
    {
        $request->validate([
            'provider' => 'required|string',
            'api_key' => 'required|string',
            'model' => 'nullable|string',
        ]);

        $provider = $request->input('provider');
        $apiKey = trim($request->input('api_key'));
        $model = $request->input('model') ?: 'gemini-2.0-flash';

        if (empty($apiKey)) {
            return response()->json(['success' => false, 'message' => 'Please enter an API Key to test.'], 422);
        }

        try {
            if (\Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($provider), 'gemini') || \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($model), 'gemini')) {
                // validate the key with the model-independent ListModels endpoint —
                // this never fails just because a model name is wrong/deprecated
                $url = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}";

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

                $resText = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr = curl_error($ch);
                curl_close($ch);

                if ($curlErr) {
                    return response()->json(['success' => false, 'message' => "Connection error: {$curlErr}"], 400);
                }

                $resJson = json_decode($resText, true);

                if ($httpCode >= 200 && $httpCode < 300) {
                    // does the chosen model actually exist for this key?
                    $available = array_map(
                        fn ($m) => str_replace('models/', '', $m['name'] ?? ''),
                        $resJson['models'] ?? []
                    );
                    $modelNote = ($model && ! in_array($model, $available, true))
                        ? " (note: “{$model}” wasn't found — pick one of the listed models)"
                        : '';

                    return response()->json([
                        'success' => true,
                        'message' => "Connected to Google Gemini — API key is valid.".$modelNote,
                        'models' => $available,
                    ]);
                }

                $lastErr = $resJson['error']['message'] ?? $resText;
                if ($httpCode === 400 || str_contains($lastErr, 'API key not valid') || str_contains($lastErr, 'API_KEY_INVALID')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid Gemini API key. Get a free key from https://aistudio.google.com/app/apikey',
                    ], 400);
                }

                return response()->json([
                    'success' => false,
                    'message' => "Gemini API error ({$httpCode}): ".\Illuminate\Support\Str::limit($lastErr, 160),
                ], 400);

            } elseif (\Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($provider), 'openai') || \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($model), 'gpt')) {
                $url = "https://api.openai.com/v1/chat/completions";

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => 'Respond with one word: Connected']],
                    'max_tokens' => 5
                ]));
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

                $resText = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr = curl_error($ch);
                curl_close($ch);

                if ($curlErr) {
                    return response()->json([
                        'success' => false,
                        'message' => "Connection error: {$curlErr}",
                    ], 400);
                }

                $resJson = json_decode($resText, true);

                if ($httpCode >= 200 && $httpCode < 300) {
                    return response()->json([
                        'success' => true,
                        'message' => "Successfully connected to OpenAI ({$model})! API key is valid.",
                    ]);
                }

                $err = $resJson['error']['message'] ?? $resText;
                return response()->json([
                    'success' => false,
                    'message' => "OpenAI API Error ({$httpCode}): " . \Illuminate\Support\Str::limit($err, 150),
                ], 400);

            } else {
                return response()->json([
                    'success' => true,
                    'message' => "API Key format saved. Ready for {$provider} ({$model}).",
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/ai/generate — Generate AI blurb or story for product creation
     */
    public function generateAiContent(Request $request)
    {
        $ai = Setting::grouped()['ai'] ?? [];
        $apiKey = trim($ai['api_key'] ?? '');

        if (empty($apiKey)) {
            return response()->json(['message' => 'Please configure your Gemini API Key in Settings > AI Integration first.'], 400);
        }

        $type = $request->input('type', 'blurb');
        $name = $request->input('name', 'Organic Tea');
        $category = $request->input('category', 'Green Tea');
        $model = $ai['model'] ?? 'gemini-2.0-flash';

        // build the prompt from the request (was previously undefined → empty request)
        if ($type === 'story') {
            $prompt = "Write a warm, premium 2-3 sentence brand story for a tea called \"{$name}\" (category: {$category}) "
                ."from Cha Kunjo, a single-origin tea brand from the hills of Sreemangal, Bangladesh. "
                ."Evoke the garden, the hand-plucking and freshness. No hashtags, no quotes.";
        } else {
            $prompt = "Write a short, elegant product blurb (max 20 words) for a tea called \"{$name}\" "
                ."(category: {$category}) from Cha Kunjo. Sensory and premium. One line, no quotes, no hashtags.";
        }

        // current model + robust fallbacks (older 1.5 / 2.0-flash names now 404)
        $modelsToTry = array_unique([$model, 'gemini-2.5-flash', 'gemini-flash-latest', 'gemini-2.5-pro']);
        $lastErr = '';

        foreach ($modelsToTry as $tryModel) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$tryModel}:generateContent?key={$apiKey}";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'contents' => [['parts' => [['text' => $prompt]]]]
            ]));
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            $resText = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $resJson = json_decode($resText, true);
            $text = $resJson['candidates'][0]['content']['parts'][0]['text'] ?? '';

            if ($httpCode >= 200 && $httpCode < 300 && !empty($text)) {
                return response()->json(['text' => trim($text)]);
            }

            $lastErr = $resJson['error']['message'] ?? $resText;
        }

        return response()->json(['message' => 'Gemini API Error: ' . \Illuminate\Support\Str::limit($lastErr, 150)], 400);
    }
}
