<?php

namespace App\Filament\Resources;

use App\Enums\TalkLength;
use App\Enums\TalkStatus;
use App\Filament\Resources\TalkResource\Pages\CreateTalk;
use App\Filament\Resources\TalkResource\Pages\ListTalks;
use App\Models\Talk;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class TalkResource extends Resource
{
    protected static ?string $model = Talk::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Talk::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->filtersTriggerAction(function ($action) {
                return $action->button()->label('Filters');
            })
            ->columns([
                TextColumn::make('title')
                    ->sortable()
                    ->searchable()
                    ->description(function (Talk $record) {
                        return Str::of($record->abstract)->limit(40);
                    })
                    ->wrap(),
                ImageColumn::make('speaker.avatar')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(function (Talk $record) {
                        return 'https://ui-avatars.com/api/?name='.urlencode($record->speaker->name).'&background=0D8ABC&color=fff';
                    }),
                TextColumn::make('speaker.name')
                    ->sortable()
                    ->searchable(),
                ToggleColumn::make('new_talk')
                    ->label('New Talk')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(function ($state) {
                        return $state->getColor();
                    }),
                Tables\Columns\IconColumn::make('length')
                    ->label('Length')
                    ->icon(function (Talk $record) {
                        return match ($record->length) {
                            TalkLength::LIGHTNING => 'heroicon-o-bolt',
                            TalkLength::NORMAL => 'heroicon-o-megaphone',
                            TalkLength::KEYNOTE => 'heroicon-o-key',
                        };
                    })
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('new_talk'),
                SelectFilter::make('speaker')
                    ->relationship('speaker', 'name')
                    ->preload()
                    ->searchable()
                    ->multiple(),
                Tables\Filters\Filter::make('has_avatar')
                    ->label('Show only Speakers with Avatars')
                    ->query(fn ($query, $state) => $query->whereHas('speaker', function ($query) use ($state) {
                        if ($state) {
                            $query->whereNotNull('avatar');
                        } else {
                            $query->whereNull('avatar');
                        }
                    })),
            ])
            ->actions([
                EditAction::make()
                    ->slideOver(),
                Tables\Actions\ActionGroup::make([
                    Action::make('approve')
                        ->label('Approve')
                        ->visible(function (Talk $record) {
                            return $record->status === TalkStatus::SUBMITTED;
                        })
                        ->action(function (Talk $record) {
                            $record->approve();
                        })
                        ->requiresConfirmation()
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->after(function () {
                            return Notification::make()
                                ->success()
                                ->duration(1500)
                                ->title('This talk has been approved')
                                ->body('The speaker has been notified and the talk has been added to the conference schedule.')
                                ->send();
                        }),
                    Action::make('reject')
                        ->label('Reject')
                        ->visible(function (Talk $record) {
                            return $record->status === TalkStatus::SUBMITTED;
                        })
                        ->action(function (Talk $record) {
                            $record->reject();
                        })
                        ->requiresConfirmation()
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->after(function () {
                            return Notification::make()
                                ->danger()
                                ->duration(1500)
                                ->title('This talk has been rejected')
                                ->body('The speaker has been notified.')
                                ->send();
                        }),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    BulkAction::make('approve')
                        ->label('Approve')
                        ->action(function (Collection $records) {
                            $records->each->approve();
                        })
                        ->requiresConfirmation()
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->after(function () {
                            return Notification::make()
                                ->success()
                                ->duration(1500)
                                ->title('Talks have been approved')
                                ->body('The speakers have been notified and the talks have been added to the conference schedule.')
                                ->send();
                        }),
                    BulkAction::make('reject')
                        ->label('Reject')
                        ->action(function (Collection $records) {
                            $records->each->reject();
                        })
                        ->requiresConfirmation()
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->after(function () {
                            return Notification::make()
                                ->danger()
                                ->duration(1500)
                                ->title('Talks have been rejected')
                                ->body('The speakers have been notified.')
                                ->send();
                        }),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('secondary')
                    ->tooltip('This will export all records visible in the table. Adjust filters to export a subset of records.')
                    ->action(function ($livewire) {
                        ray($livewire->getFilteredTableQuery()->count());
                        // Logic to export talks, e.g., to CSV or Excel
                        Notification::make()
                            ->success()
                            ->duration(1500)
                            ->title('Export initiated')
                            ->body('The talks will be exported shortly.')
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTalks::route('/'),
            'create' => CreateTalk::route('/create'),
        ];
    }
}
