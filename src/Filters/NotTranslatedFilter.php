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
                
                $connection = $query->getConnection();
                $driver = $connection->getDriverName();
                
                if ($driver === 'sqlite') {
                    // SQLite compatible query
                    return $query->where(function ($query) use ($data) {
                        return $query->whereRaw("JSON_EXTRACT(text, '$.\"{$data['lang']}\"') IS NULL")
                            ->orWhereRaw("JSON_EXTRACT(text, '$.\"{$data['lang']}\"') = '\"\"'")
                            ->orWhereRaw("JSON_EXTRACT(text, '$.\"{$data['lang']}\"') = 'null'");
                    });
                } else {
                    $jsonPath = '$."' . $data['lang'] . '"';
                    
                    return $query->where(function ($query) use ($data, $jsonPath) {
                        return $query->whereRaw("JSON_EXTRACT(text, ?) IS NULL", [$jsonPath])
                            ->orWhereRaw("JSON_EXTRACT(text, ?) = ?", [$jsonPath, ''])
                            ->orWhereRaw("JSON_EXTRACT(text, ?) = ?", [$jsonPath, '""'])
                            ->orWhereRaw("JSON_EXTRACT(text, ?) = ?", [$jsonPath, 'null'])
                            ->orWhereRaw("JSON_EXTRACT(text, ?) = ?", [$jsonPath, '"null"']);
                    });
                }
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