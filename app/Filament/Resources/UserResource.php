<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static \BackedEnum | string | null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Utilisateur';

    protected static ?string $pluralModelLabel = 'Utilisateurs';

    protected static ?string $slug = 'utilisateurs';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations personnelles')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nom')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('prenom')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),

                                TextInput::make('telephone')
                                    ->tel()
                                    ->maxLength(20),
                            ]),

                        FileUpload::make('photo')
                            ->image()
                            ->directory('photos')
                            ->disk('public')
                            ->visibility('public')
                            ->imageEditor()
                            ->circleCropper()
                            ->maxSize(2048),
                    ]),

                Section::make('Role et statut')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('role')
                                    ->options([
                                        'admin' => 'Administrateur',
                                        'coach' => 'Coach',
                                        'stagiaire' => 'Stagiaire',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->live(),

                                Toggle::make('est_actif')
                                    ->label('Compte actif')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger'),
                            ]),
                    ]),

                Section::make('Informations stagiaire')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('promotion')
                                    ->visible(fn (callable $get) => $get('role') === 'stagiaire'),

                                DatePicker::make('date_debut')
                                    ->visible(fn (callable $get) => $get('role') === 'stagiaire'),

                                DatePicker::make('date_fin')
                                    ->visible(fn (callable $get) => $get('role') === 'stagiaire'),

                                Select::make('coachs')
                                    ->multiple()
                                    ->relationship('coachs', 'email')
                                    ->preload()
                                    ->searchable()
                                    ->visible(fn (callable $get) => $get('role') === 'stagiaire'),
                            ]),
                    ])
                    ->visible(fn (callable $get) => $get('role') === 'stagiaire')
                    ->collapsible(),

                Section::make('Securite')
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->revealable(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        return 'https://ui-avatars.com/api/?background=0D8F81&color=fff&name=' . urlencode($record->prenom . ' ' . $record->nom);
                    }),

                Tables\Columns\TextColumn::make('nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('prenom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->icon('heroicon-m-envelope'),

                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'coach' => 'warning',
                        'stagiaire' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'Admin',
                        'coach' => 'Coach',
                        'stagiaire' => 'Stagiaire',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('est_actif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('promotion')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'coach' => 'Coach',
                        'stagiaire' => 'Stagiaire',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('est_actif')
                    ->label('Statut')
                    ->boolean()
                    ->trueLabel('Actifs')
                    ->falseLabel('Inactifs')
                    ->placeholder('Tous'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}