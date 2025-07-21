<?php

namespace App\Filament\Resources;

use App\Enums\TalkStatus;
use App\Filament\Resources\SpeakerResource\Pages\CreateSpeaker;
use App\Filament\Resources\SpeakerResource\Pages\ListSpeakers;
use App\Filament\Resources\SpeakerResource\Pages\ViewSpeaker;
use App\Filament\Resources\SpeakerResource\RelationManagers\TalksRelationManager;
use App\Models\Speaker;
use Filament\Forms\Form;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SpeakerResource extends Resource
{
    protected static ?string $model = Speaker::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Speaker::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('twitter_handle')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Personal Information')
                    ->columns(3)
                    ->schema([
                        ImageEntry::make('avatar')
                            ->circular(),
                        Group::make()
                            ->columnSpan(2)
                            ->columns(2)
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('email'),
                                TextEntry::make('twitter_handle')
                                    ->label('Twitter')
                                    ->getStateUsing(function ($record) {
                                        return '@'.$record->twitter_handle;
                                    })
                                    ->url(fn (Speaker $record): string => 'https://x.com/'.$record->twitter_handle),
                                TextEntry::make('has_spoken')
                                    ->getStateUsing(fn (Speaker $record): string => $record->talks()->where('status', TalkStatus::APPROVED)->count() > 0 ? 'Previous Speaker' : 'Has Not Spoken')
                                    ->badge()
                                    ->color(fn (Speaker $record): string => $record->talks()->where('status', TalkStatus::APPROVED)->count() > 0 ? 'success' : 'primary'),
                            ]),
                    ]),
                Section::make('Other Information')
                    ->schema([
                        TextEntry::make('bio')
                            ->columnSpanFull()
                            ->markdown()
                            ->getStateUsing(fn (Speaker $record): string => $record->bio ?? 'No bio provided.'),
                        TextEntry::make('qualifications'),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TalksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpeakers::route('/'),
            'create' => CreateSpeaker::route('/create'),
            'view' => ViewSpeaker::route('/{record}'),
        ];
    }
}
