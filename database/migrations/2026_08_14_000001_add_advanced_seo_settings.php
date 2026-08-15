<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddAdvancedSeoSettings extends Migration
{
    public function up()
    {
        $now = now();
        $settings = [
            'site_name' => 'Cha Kunjo',
            'logo' => '/images/slider/1.jpeg',
            'locality' => 'Sreemangal, Bangladesh',
            'business_phone' => '01313762119',
            'business_email' => 'chakunjo@gmail.com',
            'social_profiles' => 'https://facebook.com/chakunjo',
            'google_site_verification' => '',
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->insertOrIgnore([
                'group' => 'seo',
                'key' => 'seo.' . $key,
                'value' => $value,
                'type' => 'string',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('settings')->where('key', 'seo.business_email')->where('value', '')
            ->update(['value' => 'chakunjo@gmail.com', 'updated_at' => $now]);
        DB::table('settings')->where('key', 'seo.business_phone')->where('value', '')
            ->update(['value' => '01313762119', 'updated_at' => $now]);

        foreach (['email' => 'chakunjo@gmail.com', 'phone' => '01313762119'] as $key => $value) {
            DB::table('settings')->insertOrIgnore([
                'group' => 'store', 'key' => 'store.' . $key, 'value' => $value,
                'type' => 'string', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        DB::table('settings')->where('key', 'store.email')
            ->whereIn('value', ['', 'hello@chakunjo.com'])
            ->update(['value' => 'chakunjo@gmail.com', 'updated_at' => $now]);
        DB::table('settings')->where('key', 'store.phone')
            ->whereIn('value', ['', '+880 1XXX-XXXXXX', '+880 1712-345678'])
            ->update(['value' => '01313762119', 'updated_at' => $now]);

        DB::table('settings')->insertOrIgnore([
            'group' => 'notifications', 'key' => 'notifications.notification_email',
            'value' => 'chakunjo@gmail.com', 'type' => 'string',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('settings')->where('key', 'notifications.notification_email')->where('value', '')
            ->update(['value' => 'chakunjo@gmail.com', 'updated_at' => $now]);

        DB::table('footer_links')->where('col', 'contact')
            ->whereIn('label', ['+880 1XXX-XXXXXX', '+880 1712-345678'])
            ->update(['label' => '01313762119', 'updated_at' => $now]);
    }

    public function down()
    {
        // Non-destructive by design: never remove settings on rollback.
    }
}
