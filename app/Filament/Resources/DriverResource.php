<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriverResource\Pages;
use App\Models\Driver;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DriverResource extends Resource
{
    protected static ?string $model = Driver::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'المستخدمون';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'السائقون';
    protected static ?string $modelLabel = 'سائق';
    protected static ?string $pluralModelLabel = 'السائقون';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('الاسم')->required(),
            Forms\Components\TextInput::make('email')->label('البريد')->email()->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('phone')->label('الهاتف')->tel()->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('password')
                ->label('كلمة المرور')
                ->password()
                ->revealable()
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn ($state) => filled($state)) // لا تُحفظ إن تُركت فارغة عند التعديل
                ->helperText('اتركها فارغة عند التعديل للإبقاء على كلمة المرور الحالية'),
            Forms\Components\Select::make('availability')
                ->label('حالة الاتصال')
                ->options([
                    'online'  => 'متصل',
                    'offline' => 'غير متصل',
                    'busy'    => 'مشغول',
                ])->default('offline')->required(),
            Forms\Components\Toggle::make('is_active')->label('الحساب مفعّل')->default(true),
            Forms\Components\TextInput::make('rating_average')->label('متوسط التقييم')->numeric()->disabled()->dehydrated(false),
            Forms\Components\TextInput::make('rating_count')->label('عدد التقييمات')->numeric()->disabled()->dehydrated(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('الهاتف')->searchable(),
                Tables\Columns\TextColumn::make('rating_average')->label('التقييم')->numeric(2)->sortable(),
                Tables\Columns\TextColumn::make('rating_count')->label('عدد التقييمات')->numeric()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('availability')->label('الحالة')->badge()->color(fn (string $state) => match ($state) {
                    'online' => 'success', 'busy' => 'warning', default => 'gray',
                })->formatStateUsing(fn (string $state) => match ($state) {
                    'online' => 'متصل', 'busy' => 'مشغول', default => 'غير متصل',
                }),
                Tables\Columns\IconColumn::make('is_active')->label('مفعّل')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('انضمّ في')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('مفعّل'),
                Tables\Filters\SelectFilter::make('availability')->label('الحالة')->options([
                    'online' => 'متصل', 'offline' => 'غير متصل', 'busy' => 'مشغول',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (Driver $r) => $r->is_active ? 'تعطيل' : 'تفعيل')
                    ->icon(fn (Driver $r) => $r->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (Driver $r) => $r->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (Driver $r) => $r->update(['is_active' => ! $r->is_active])),
                Tables\Actions\EditAction::make()->label('تعديل'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDrivers::route('/'),
            'create' => Pages\CreateDriver::route('/create'),
            'edit'   => Pages\EditDriver::route('/{record}/edit'),
        ];
    }
}
