<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type'];

    /** cast the stored text value back to its real type */
    public function getTypedValueAttribute()
    {
        switch ($this->type) {
            case 'bool':
                return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
            case 'int':
                return (int) $this->value;
            default:
                return $this->value;
        }
    }

    /** all settings as { group: { shortKey: typedValue } } */
    public static function grouped(): array
    {
        $out = [];
        foreach (static::all() as $s) {
            $short = str_contains($s->key, '.') ? explode('.', $s->key, 2)[1] : $s->key;
            $out[$s->group][$short] = $s->typed_value;
        }
        return $out;
    }

    public static function put(string $group, string $short, $value, string $type = 'string'): void
    {
        $stored = $type === 'bool' ? ($value ? '1' : '0') : (string) $value;
        static::updateOrCreate(
            ['key' => "{$group}.{$short}"],
            ['group' => $group, 'value' => $stored, 'type' => $type]
        );
    }
}
