<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RideResource\Pages;
use App\Models\Ride;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RideResource extends Resource
{
    protected static ?string $model = Ride::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'العمليات';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'الرحلات';
    protected static ?string $modelLabel = 'رحلة';
    protected static ?string $pluralModelLabel = 'الرحلات';

    public static function canCreate(): bool
    {
        return false; // الرحلات تُنشأ من التطبيق فقط — العرض للمراقبة
    }

    public static array $statusOptions = [
        'pending'        => 'قيد الانتظار',
        'accepted'       => 'مقبولة',
        'driver_arrived' => 'وصل السائق',
        'in_progress'    => 'جارية',
        'completed'      => 'مكتملة',
        'cancelled'      => 'ملغاة',
        'rejected'       => 'مرفوضة',
    ];

    public static function form(Form $form): Form
    {
        // نموذج للعرض فقط (كل الحقول معطّلة)
        return $form->schema([
            Forms\Components\Placeholder::make('customer')->label('العميل')->content(fn (?Ride $r) => $r?->customer?->name ?? '—'),
            Forms\Components\Placeholder::make('driver')->label('السائق')->content(fn (?Ride $r) => $r?->driver?->name ?? '—'),
            Forms\Components\Placeholder::make('status')->label('الحالة')->content(fn (?Ride $r) => self::$statusOptions[$r?->status] ?? $r?->status),
            Forms\Components\Placeholder::make('pickup_address')->label('نقطة الانطلاق')->content(fn (?Ride $r) => $r?->pickup_address ?? '—'),
            Forms\Components\Placeholder::make('destination_address')->label('الوجهة')->content(fn (?Ride $r) => $r?->destination_address ?? '—'),
            Forms\Components\Placeholder::make('distance_km')->label('المسافة (كم)')->content(fn (?Ride $r) => $r?->distance_km ?? '—'),
            Forms\Components\Placeholder::make('estimated_fare')->label('الأجرة التقديرية')->content(fn (?Ride $r) => $r?->estimated_fare ?? '—'),
            Forms\Components\Placeholder::make('final_fare')->label('الأجرة النهائية')->content(fn (?Ride $r) => $r?->final_fare ?? '—'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('customer.name')->label('العميل')->searchable()->default('—'),
                Tables\Columns\TextColumn::make('driver.name')->label('السائق')->searchable()->default('—'),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()->color(fn (string $state) => match ($state) {
                    'completed' => 'success', 'in_progress' => 'info', 'cancelled', 'rejected' => 'danger', default => 'warning',
                })->formatStateUsing(fn (string $state) => self::$statusOptions[$state] ?? $state),
                Tables\Columns\TextColumn::make('final_fare')->label('الأجرة')->numeric(2)->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('التاريخ')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('الحالة')->options(self::$statusOptions),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تفاصيل'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRides::route('/'),
            'edit'  => Pages\EditRide::route('/{record}/edit'),
        ];
    }
}
