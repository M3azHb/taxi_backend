<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'العمليات';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'المدفوعات';
    protected static ?string $modelLabel = 'دفعة';
    protected static ?string $pluralModelLabel = 'المدفوعات';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('ride_id')->label('الرحلة')->sortable(),
                Tables\Columns\TextColumn::make('amount')->label('المبلغ')->numeric(2)->sortable(),
                Tables\Columns\TextColumn::make('commission_amount')->label('عمولة الشركة')->numeric(2)->sortable(),
                Tables\Columns\TextColumn::make('driver_earning')->label('ربح السائق')->numeric(2)->sortable(),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()->color(fn (string $state) => $state === 'paid' ? 'success' : 'warning')
                    ->formatStateUsing(fn (string $state) => $state === 'paid' ? 'مدفوعة' : 'معلّقة'),
                Tables\Columns\TextColumn::make('paid_at')->label('تاريخ الدفع')->dateTime('Y-m-d H:i')->placeholder('—')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('الحالة')->options([
                    'pending' => 'معلّقة', 'paid' => 'مدفوعة',
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
        ];
    }
}
