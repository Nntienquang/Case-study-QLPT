<?php

class PasswordInput
{
    public static function render(array $props): void
    {
        $name = (string)($props['name'] ?? 'password');
        $label = (string)($props['label'] ?? 'Mật khẩu');
        $placeholder = (string)($props['placeholder'] ?? '');
        $id = (string)($props['id'] ?? self::idFromName($name));
        $autocomplete = (string)($props['autocomplete'] ?? 'current-password');
        $required = array_key_exists('required', $props) ? (bool)$props['required'] : true;
        $minlength = isset($props['minlength']) ? (int)$props['minlength'] : 0;

        $attrs = [
            'type' => 'password',
            'id' => $id,
            'name' => $name,
            'placeholder' => $placeholder,
            'autocomplete' => $autocomplete,
        ];

        if ($required) {
            $attrs['required'] = null;
        }
        if ($minlength > 0) {
            $attrs['minlength'] = (string)$minlength;
        }
        ?>
        <label for="<?php echo self::e($id); ?>"><?php echo self::e($label); ?></label>
        <div class="input-group password-input" data-password-input>
            <i class="fa fa-lock password-input__leading" aria-hidden="true"></i>
            <input <?php echo self::attrs($attrs); ?>>
            <button
                type="button"
                class="password-input__toggle"
                data-password-toggle
                aria-label="Hiện mật khẩu"
                aria-pressed="false"
                aria-controls="<?php echo self::e($id); ?>"
                title="Hiện mật khẩu"
            >
                <i class="fa fa-eye" aria-hidden="true"></i>
            </button>
        </div>
        <?php
    }

    private static function idFromName(string $name): string
    {
        return 'password_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    }

    private static function attrs(array $attrs): string
    {
        $html = [];
        foreach ($attrs as $key => $value) {
            if ($value === null) {
                $html[] = self::e((string)$key);
                continue;
            }
            $html[] = self::e((string)$key) . '="' . self::e((string)$value) . '"';
        }

        return implode(' ', $html);
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
