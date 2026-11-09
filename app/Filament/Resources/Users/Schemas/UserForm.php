<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->required()->maxLength(120),
            TextInput::make('email')->label('Email address')->email()->required()->maxLength(190)->unique(ignoreRecord: true),
            TextInput::make('password')->password()->revealable()
                ->rule(Password::min(10)->letters()->numbers())
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn (?string $state) => filled($state))
                ->helperText(fn (string $operation) => $operation === 'edit' ? 'Leave blank to keep the current password.' : 'At least 10 characters with letters and numbers.'),
            Select::make('roles')->relationship('roles', 'name')->multiple()->preload()->required()
                ->helperText('super-admin: everything · editor: all content · enquiry-handler: enquiries only'),
            Toggle::make('is_active')->label('Active (can log in)')->default(true),
        ]);
    }
}
