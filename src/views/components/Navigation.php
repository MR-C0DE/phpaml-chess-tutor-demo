<?php

declare(strict_types=1);

namespace App\Views\Components;

use AML\View\{Component, View};
use function AML\View\{Button, Element, Link, Text, ThemeSwitcher};

final class Navigation extends Component
{
    public function body(): View
    {
        return Element('header',
            Element('nav',
                Link('TUTOR', '/')->class('brand'),
                Element('div',
                    Link('Train', '#training'), Link('Lessons', '#lessons'),
                    Element('div',
                        Button('EN')->attribute('type', 'button')->attribute('data-locale', 'en')->attribute('aria-pressed', 'true'),
                        Button('FR')->attribute('type', 'button')->attribute('data-locale', 'fr')->attribute('aria-pressed', 'false'),
                    )->class('language-switcher')->attribute('aria-label', 'Language'),
                    ThemeSwitcher('light', 'dark', 'system')->class('theme-switcher'),
                    Button('Sign in')->attribute('type', 'button')->attribute('data-action', 'signin')->attribute('id', 'account-button')->class('nav-button'),
                )->class('nav-links'),
            )->class('site-nav', 'shell'),
        )->class('site-header');
    }
}

function Navigation(): Navigation { return new Navigation(); }
