<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CarTypeResource\Pages;
use App\Models\CarType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CarTypeResource extends Resource
{
    protected static ?string $model = CarType::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'الإعدادات';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'أنواع السيارات';
    protected static ?string $modelLabel = 'نوع سيارة';
    protected static ?string $pluralModelLabel = 'أنواع السيارات';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('type_name')->label('الاسم')->required(),
            Forms\Components\TextInput::make('base_fare')->label('الأجرة الأساسية')->numeric()->required()->suffix('ل.س'),
            Forms\Components\TextInput::make('price_per_km')->label('سعر الكيلومتر')->numeric()->required()->suffix('ل.س'),
            Forms\Components\TextInput::make('description')->label('الوصف')->maxLength(255),
            Forms\Components\Toggle::make('is_active')->label('مفعّل')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type_name')->label('الاسم')->searchable(),
                Tables\Columns\TextColumn::make('base_fare')->label('الأجرة الأساسية')->numeric(2)->sortable(),
                Tables\Columns\TextColumn::make('price_per_km')->label('سعر الكيلومتر')->numeric(2)->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('مفعّل')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCarTypes::route('/'),
            'create' => Pages\CreateCarType::route('/create'),
            'edit'   => Pages\EditCarType::route('/{record}/edit'),
        ];
    }
}
