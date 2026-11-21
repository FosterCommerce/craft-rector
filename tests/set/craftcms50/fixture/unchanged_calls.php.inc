<?php

class OverridingElementFoo extends \craft\base\Element
{
    public function foo(): void {
        $own = $this->getThumbUrl(10);
    }

    public function getThumbUrl(int $size): ?string {
        return 'custom';
    }

    protected function thumbUrl(int $size): ?string {
        return 'craft';
    }
}

class ParentCallFieldFoo extends \craft\base\Field
{
    protected function inputHtml(mixed $value, ?\craft\base\ElementInterface $element, bool $inline = false): string {
        return parent::inputHtml(value: $value, element: $element, inline: $inline);
    }

    public function helper(mixed $value): string {
        return parent::inputHtml($value, null);
    }
}

?>
