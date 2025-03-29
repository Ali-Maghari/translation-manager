<?php

namespace Kenepa\TranslationManager\Filters;

use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class NotTranslatedFilter extends Filter
{
    public static function make(?string $name = null): static
    {
        return parent::make('not-translated')
            ->form([
                Select::make('lang')
                    ->label(__('translation-manager::translations.filter-not-translated'))
                    ->options(function () {
                        $options = [];
                        foreach (config('translation-manager.available_locales') as $locale) {
                            $options[$locale['code']] = $locale['name'];
                        }
                        return $options;
                    })
                    ->required()
                    ->searchable(),
            ])
            ->query(function (Builder $query, array $data): Builder {
                if (!isset($data['lang']) || empty($data['lang'])) {
                    return $query;
                }
                
                return $query->whereRaw("NOT EXISTS (SELECT 1 FROM JSON_EACH(text) WHERE JSON_EACH.key = ?)", [$data['lang']])
                    ->orWhereRaw("JSON_EXTRACT(text, ?) IS NULL", ['$."' . $data['lang'] . '"'])
                    ->orWhereRaw("JSON_EXTRACT(text, ?) = ''", ['$."' . $data['lang'] . '"']);
            })
            ->indicateUsing(function (array $data): ?string {
                if (!isset($data['lang']) || empty($data['lang'])) {
                    return null;
                }
                
                $langName = collect(config('translation-manager.available_locales'))
                    ->firstWhere('code', $data['lang'])['name'] ?? strtoupper($data['lang']);
                
                return __('translation-manager::translations.not-translated-in', ['lang' => $langName]);
            });
    }
}