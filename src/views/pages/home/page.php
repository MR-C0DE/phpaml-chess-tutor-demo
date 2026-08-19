<?php

declare(strict_types=1);

namespace App\Views\Pages\Home;

use AML\View\{Page, PageMetadata, View};
use function AML\View\{Button, Element, Heading, MainContent, Section, Text};

final class HomePage extends Page
{
    public function metadata(): PageMetadata
    {
        return (new PageMetadata())
            ->title('Tutor Chess — Your AI chess mentor')
            ->description('Play, understand every move, and turn every game into a lesson with Tutor.');
    }

    public function body(): View
    {
        return MainContent(
            Section(
                Element('div',
                    Text('PERSONAL CHESS MENTOR')->class('eyebrow'),
                    Heading('Play a move. Understand the idea.', 1)->class('hero-title'),
                    Text('Tutor reviews every decision, answers on the board and saves the lesson so your next game starts smarter.')->class('hero-copy'),
                    Element('div',
                        Button('Start training')->attribute('type', 'button')->attribute('data-action', 'start')->class('primary-button'),
                        Button('Sign in')->attribute('type', 'button')->attribute('data-action', 'signin')->class('ghost-button'),
                    )->class('hero-actions'),
                )->class('hero-content'),
                Element('div',
                    Element('span', Text('Tutor online'))->class('live-pill'),
                    Text('“Develop with purpose. I’ll explain the position after every move.”')->class('tutor-quote'),
                    Element('div', Text('T'))->class('tutor-avatar'),
                )->class('mentor-card'),
            )->class('hero', 'shell'),
            Section(
                Element('div', Text('01')->class('value-number'), Heading('Play naturally', 3), Text('Real chess rules, legal moves and fluid piece movement.')->class('muted'))->class('value-item'),
                Element('div', Text('02')->class('value-number'), Heading('Learn immediately', 3), Text('Tutor evaluates the idea while the position is still fresh.')->class('muted'))->class('value-item'),
                Element('div', Text('03')->class('value-number'), Heading('Build a record', 3), Text('Every explanation becomes part of your private lesson history.')->class('muted'))->class('value-item'),
            )->class('value-strip', 'shell'),
            Section(
                Element('div',
                    Text('LIVE TRAINING')->class('eyebrow'),
                    Heading('Your board. Your coach. One focused session.', 2),
                    Text('Play White and let Tutor challenge every decision in real time.')->class('muted'),
                )->class('training-heading', 'shell'),
                Element('div',
                    Element('div',
                        Element('div')->attribute('id', 'chess-board')->class('chess-board')->attribute('aria-label', 'Chess board'),
                        Element('div',
                            Text('You play White')->class('board-label'),
                            Text('Select a piece, then its destination.')->attribute('id', 'board-status')->class('board-status'),
                        )->class('board-footer'),
                    )->class('board-panel'),
                    Element('aside',
                    Element('div',
                        Element('span', Text('T'))->class('mini-avatar'),
                        Element('div', Heading('Tutor', 2), Text('DeepSeek chess mentor')->class('muted')),
                        Element('span', Text('Ready'))->attribute('id', 'tutor-state')->class('status-pill'),
                    )->class('tutor-heading'),
                    Element('div',
                        Text('Make your first move. I will evaluate the idea, reply, and explain what to learn from it.')
                            ->attribute('id', 'tutor-message')->class('tutor-message'),
                        Text('Tip: fight for the centre and develop a new piece early.')->attribute('id', 'tutor-tip')->class('tutor-tip'),
                    )->class('feedback-card'),
                    Element('div',
                        Element('div', Text('—')->attribute('id', 'lesson-score')->class('metric-value'), Text('Lesson score')->class('metric-label')),
                        Element('div', Text('0')->attribute('id', 'move-count')->class('metric-value'), Text('Moves reviewed')->class('metric-label')),
                    )->class('metrics'),
                    Button('New lesson')->attribute('type', 'button')->attribute('data-action', 'new-game')->class('secondary-button'),
                    )->class('tutor-panel'),
                )->class('training-grid', 'shell'),
            )->attribute('id', 'training')->class('training-section'),
            Section(
                Element('div', Text('YOUR PROGRESS')->class('eyebrow'), Heading('Every game becomes a lesson.', 2), Text('Your recent sessions appear here after you sign in.')->class('muted'))->class('section-heading'),
                Element('div', Text('Sign in to see your saved lessons.')->class('empty-state'))->attribute('id', 'lesson-list')->class('lesson-list'),
            )->class('lessons-section', 'shell'),
            Element('div',
                Element('div',
                    Button('×')->attribute('type', 'button')->attribute('data-action', 'close-auth')->attribute('aria-label', 'Close')->class('modal-close'),
                    Text('WELCOME TO TUTOR')->class('eyebrow'),
                    Heading('Save every lesson', 2),
                    Text('Create an account or continue where you stopped.')->class('muted'),
                    Element('div',
                        Button('Sign in')->attribute('type', 'button')->attribute('data-auth-tab', 'login')->class('auth-tab', 'is-active'),
                        Button('Create account')->attribute('type', 'button')->attribute('data-auth-tab', 'register')->class('auth-tab'),
                    )->class('auth-tabs'),
                    Element('form',
                        Element('label', Text('Name'), Element('input')->attribute('name', 'name')->attribute('autocomplete', 'name')->class('auth-input'))->attribute('data-register-only', 'true')->class('field', 'register-field'),
                        Element('label', Text('Email'), Element('input')->attribute('name', 'email')->attribute('type', 'email')->attribute('required', 'required')->attribute('autocomplete', 'email')->class('auth-input'))->class('field'),
                        Element('label', Text('Password'), Element('input')->attribute('name', 'password')->attribute('type', 'password')->attribute('required', 'required')->attribute('minlength', '8')->attribute('autocomplete', 'current-password')->class('auth-input'))->class('field'),
                        Text('')->attribute('id', 'auth-error')->class('form-error'),
                        Button('Sign in')->attribute('type', 'submit')->attribute('id', 'auth-submit')->class('primary-button', 'wide'),
                    )->attribute('id', 'auth-form')->class('auth-form'),
                )->class('auth-dialog'),
            )->attribute('id', 'auth-modal')->attribute('aria-hidden', 'true')->class('modal'),
        )->class('tutor-app');
    }
}
