<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    protected static array $statuses = [
        'in stock' => 'in stock',
        'sold out' => 'sold out',
        'coming soon' => 'coming soon',
    ];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
//                Forms\Components\Section::make('Main data')
//                    ->description('What users totally need to fill in')
//                    ->schema([
//                Forms\Components\Tabs::make()->tabs([
//                    Forms\Components\Tabs\Tab::make('Main data')
//                        ->schema([

                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Main data')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->unique(ignoreRecord: true),
                            Forms\Components\TextInput::make('price')
                                ->required()
                                ->rule('numeric'),
                        ]),
                    //Forms\Components\Section::make('Additional data')
                    //Forms\Components\Fieldset::make('Additional data')
                    //Forms\Components\Tabs\Tab::make('Additional data')
                    Forms\Components\Wizard\Step::make('Additional data')
                        ->schema([
                            Forms\Components\Radio::make('status')
                                ->options(self::$statuses),
                            Forms\Components\Select::make('category_id')
                                ->relationship('category', 'name'),
                        ]),
//                Forms\Components\Select::make('tags')
//                    ->relationship('tags', 'name')
//                    ->multiple(),
                ]),
                Forms\Components\RichEditor::make('description')
                    ->columnSpanFull()
                    ->required(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextInputColumn::make('name')
                    ->rules(['required', 'min:3'])
                    //Tables\Columns\TextColumn::make('name')
                    ->searchable(isIndividual: true, isGlobal: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->sortable()
                    ->searchable()
                    ->money('usd')
                    ->getStateUsing(function (Product $record): float {
                        return $record->price / 100;
                    })
                    ->alignment(Alignment::End),
//                Tables\Columns\TextColumn::make('status')
//                    ->badge()
//                    ->color(fn(string $state): string => match ($state) {
//                        'in stock' => 'primary',
//                        'sold out' => 'danger',
//                        'coming soon' => 'info',
//                    }),
                Tables\Columns\SelectColumn::make('status')
                    ->options(self::$statuses),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->onColor('primary') // default value: "primary"
                    ->offColor('danger'),
                //Tables\Columns\CheckboxColumn::make('is_active'),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category name'),
                Tables\Columns\TextColumn::make('tags.name'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
                //->since(),
                //->dateTime('m/d/Y H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(self::$statuses),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\Filter::make('created_from')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('created_until')
                    ->form([
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('price', 'desc')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TagsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
