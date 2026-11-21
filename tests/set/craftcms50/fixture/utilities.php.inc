<?php

class UtilityFoo extends \craft\base\Utility
{
    public static function iconPath(): ?string {
        return 'icon.svg';
    }
}

?>
-----
<?php

class UtilityFoo extends \craft\base\Utility
{
    public static function icon(): ?string {
        return 'icon.svg';
    }
}

?>
