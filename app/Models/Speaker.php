<?php

namespace App\Models;

use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Speaker extends Model
{
    use HasFactory;

    const QUALIFICATIONS = [
        'business-leader' => 'Business Leader',
        'charisma' => 'Charismatic Speaker',
        'first-time' => 'First Time Speaker',
        'hometown-hero' => 'Hometown Hero',
        'humanitarian' => 'Works in Humanitarian Field',
        'laracasts-contributor' => 'Laracasts Contributor',
        'twitter-influencer' => 'Large Twitter Following',
        'youtube-influencer' => 'Large Youtube Following',
        'open-source' => 'Open Source Creator / Maintainer',
        'unique-perspective' => 'Unique Perspective',
    ];

    const DESCRIPTIONS = [
        'business-leader' => 'This person is a leader in the business community and has valuable insights to share',
        'charisma' => 'This person is a charismatic speaker and can engage the audience',
        'first-time' => 'This is a great first time speaker',
        'hometown-hero' => 'This person is a local hero in the community',
        'humanitarian' => 'This person works in the humanitarian field and has a lot to share',
        'laracasts-contributor' => 'This person has contributed to Laracasts and has valuable insights',
        'twitter-influencer' => 'This person has a large following on Twitter and can reach many people',
        'youtube-influencer' => 'This person has a large following on YouTube and can reach many people',
        'open-source' => 'This person is an open source creator or maintainer and has valuable insights',
        'unique-perspective' => 'This person has a unique perspective that can benefit the conference attendees',
    ];

    public static function getForm(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            FileUpload::make('avatar')
                ->avatar()
                ->imageEditor()
                ->maxSize(1024 * 1024 * 10),
            TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255),
            MarkdownEditor::make('bio')
                ->columnSpanFull(),
            TextInput::make('twitter_handle')
                ->maxLength(255),
            CheckboxList::make('qualifications')
                ->columnSpanFull()
                ->searchable()
                ->bulkToggleable()
                ->options(self::QUALIFICATIONS)
                ->descriptions(self::DESCRIPTIONS)
                ->columns(3),
            Actions::make(
                [
                    Action::make('star')
                        ->label('Fill with Factory Data')
                        ->icon('heroicon-m-star')
                        ->visible(
                            function (string $operation) {
                                if ($operation !== 'create') {
                                    return false;
                                }
                                if (! app()->environment('local')) {
                                    return false;
                                }

                                return true;
                            })
                        ->action(
                            function ($livewire) {
                                $data = Speaker::factory()->make()->toArray();
                                $livewire->form->fill($data);
                            }),
                ]),
        ];
    }

    public function talks(): HasMany
    {
        return $this->hasMany(Talk::class, 'speaker_id');
    }

    public function conferences(): BelongsToMany
    {
        return $this->belongsToMany(Conference::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'qualifications' => 'array',
        ];
    }
}
