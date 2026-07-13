<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountCodeResource\Pages;
use App\Models\DiscountCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DiscountCodeResource extends Resource
{
    protected static ?string $model = DiscountCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'الإعدادات';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'أكواد الخصم';
    protected static ?string $modelLabel = 'كود خصم';
    protected static ?string $pluralModelLabel = 'أكواد الخصم';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->label('الكود')->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('discount_percentage')->label('نسبة الخصم %')->numeric()->required()->minValue(1)->maxValue(100),
            Forms\Components\DatePicker::make('expiry_date')->label('تاريخ الانتهاء')->required(),
            Forms\Components\TextInput::make('usage_limit')->label('حد الاستخدام (0 = بلا حدّ)')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->label('مفعّل')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('الكود')->searchable()->badge(),
                Tables\Columns\TextColumn::make('discount_percentage')->label('الخصم %')->numeric(2)->sortable(),
                Tables\Columns\TextColumn::make('used_count')->label('استُخدم')->numeric(),
                Tables\Columns\TextColumn::make('usage_limit')->label('الحدّ')->numeric(),
                Tables\Columns\TextColumn::make('expiry_date')->label('ينتهي')->date()->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('مفعّل')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('مفعّل'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDiscountCodes::route('/'),
            'create' => Pages\CreateDiscountCode::route('/create'),
            'edit'   => Pages\EditDiscountCode::route('/{record}/edit'),
        ];
    }
}
