<?php

namespace App\Models;

use App\Enums\Region;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Conference extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'region' => Region::class,
            'venue_id' => 'integer',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function speakers(): BelongsToMany
    {
        return $this->belongsToMany(Speaker::class);
    }

    public function talks(): BelongsToMany
    {
        return $this->belongsToMany(Talk::class);
    }

    public static function getForm(): array
    {
        return [
            TextInput::make('name')
                ->label('Conference Name')
                ->required()
                ->maxLength(60),
            MarkdownEditor::make('description')
                ->required(),
            DatePicker::make('start_date')
                ->native(false)
                ->required(),
            DatePicker::make('end_date')
                ->native(false)
                ->required(),
            Checkbox::make('is_published')
                ->label('Is Published')
                ->default(true),
            Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'archived' => 'Archived',
                ])
                ->required(),
            Select::make('region')
                ->live()
                ->enum(Region::class)
                ->options(Region::class),
            Select::make('venue_id')
                ->searchable()
                ->preload()
                ->createOptionForm(Venue::getForm())
                ->editOptionForm(Venue::getForm())
                ->relationship('venue', 'name', modifyQueryUsing: function (Builder $query, Forms\Get $get): Builder {
                    return $query->where('region', $get('region'));
                }),
            CheckboxList::make('speakers')
                ->relationship('speakers', 'name')
                ->options(Speaker::all()->pluck('name', 'id'))
        ];
    }
}
