<?php

class TwigExtensionFoo extends \Twig_Extension
{
    public function getFilters(): array {
        return [new \Twig_SimpleFilter('foo', fn () => 'bar')];
    }
}

?>
-----
<?php

class TwigExtensionFoo extends \Twig\Extension\AbstractExtension
{
    public function getFilters(): array {
        return [new \Twig\TwigFilter('foo', fn () => 'bar')];
    }
}

?>
