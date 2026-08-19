<?php

declare(strict_types=1);

namespace App\Views\Layouts;

use AML\View\Layout;
use AML\View\View;
use function App\Views\Components\Navigation;
use function AML\View\{Element, Link, Slot, Text, ThemeProvider, VStack};

final class AppLayout extends Layout
{
    public function body(): View
    {
        return ThemeProvider(
            default: 'system',
            content: VStack(
                Navigation(),
                Slot(),
                Element('footer',
                    Text('Tutor Chess · Built with PHPAML View, PHPAML Data and DeepSeek')->class('footer-copy'),
                    Link('PHPAML', 'https://phpaml.com'),
                )->class('view-footer', 'shell'),
            )->class('view-app'),
            themes: ['light', 'dark'],
        );
    }
}
