<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'العمليات';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'البلاغات';
    protected static ?string $modelLabel = 'بلاغ';
    protected static ?string $pluralModelLabel = 'البلاغات';

    // شارة تُظهر عدد البلاغات المعلّقة على أيقونة القائمة
    public static function getNavigationBadge(): ?string
    {
        $count = Report::where('status', Report::STATUS_PENDING)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canCreate(): bool
    {
        return false; // البلاغات تأتي من التطبيقات فقط
    }

    protected static array $statusOptions = [
        'pending'   => 'قيد الانتظار',
        'reviewed'  => 'تمت المراجعة',
        'resolved'  => 'تم الحل',
        'dismissed' => 'مرفوض',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('reporter')
                ->label('المُبلِّغ')
                ->content(fn (?Report $record) => $record?->reporter?->name ?? '—'),
            Forms\Components\Placeholder::make('reported')
                ->label('المُبلَّغ عنه')
                ->content(fn (?Report $record) => $record?->reported?->name ?? '—'),
            Forms\Components\Placeholder::make('ride')
                ->label('رقم الرحلة')
                ->content(fn (?Report $record) => $record?->ride_id ?? 'بلاغ عام'),
            Forms\Components\Textarea::make('description')
                ->label('الوصف')->disabled()->rows(4)->columnSpanFull(),
            Forms\Components\Select::make('status')
                ->label('الحالة')
                ->options(self::$statusOptions)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('reporter.name')->label('المُبلِّغ')->default('—'),
                Tables\Columns\TextColumn::make('reported.name')->label('المُبلَّغ عنه')->default('—'),
                Tables\Columns\TextColumn::make('description')->label('الوصف')->limit(40)->wrap(),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()->color(fn (string $state) => match ($state) {
                    'pending' => 'warning', 'reviewed' => 'info', 'resolved' => 'success', default => 'gray',
                })->formatStateUsing(fn (string $state) => self::$statusOptions[$state] ?? $state),
                Tables\Columns\TextColumn::make('created_at')->label('التاريخ')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('الحالة')->options(self::$statusOptions),
            ])
            ->actions([
                Tables\Actions\Action::make('resolve')->label('حلّ')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (Report $r) => $r->status !== Report::STATUS_RESOLVED)
                    ->requiresConfirmation()
                    ->action(fn (Report $r) => $r->updateStatus(Report::STATUS_RESOLVED)),
                Tables\Actions\Action::make('dismiss')->label('رفض')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (Report $r) => $r->status !== Report::STATUS_DISMISSED)
                    ->requiresConfirmation()
                    ->action(fn (Report $r) => $r->updateStatus(Report::STATUS_DISMISSED)),
                Tables\Actions\EditAction::make()->label('تفاصيل'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'edit'  => Pages\EditReport::route('/{record}/edit'),
        ];
    }
}
