<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

/**
 * Base for single-form admin screens (site settings, header settings).
 * Subclasses provide: fields(), load(), store(), permission().
 */
abstract class SettingsPage extends Page
{
    protected string $view = 'filament.pages.settings-form';

    /** @var array<string,mixed>|null */
    public ?array $data = [];

    abstract protected static function permission(): string;

    /** @return array<int,\Filament\Schemas\Components\Component> */
    abstract protected function fields(): array;

    /** @return array<string,mixed> */
    abstract protected function load(): array;

    /** @param array<string,mixed> $data */
    abstract protected function store(array $data): void;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can(static::permission());
    }

    public function mount(): void
    {
        $this->form->fill($this->load());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components($this->fields())->statePath('data');
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->store($this->form->getState());

        Notification::make()->success()->title('Saved')->send();
    }
}
