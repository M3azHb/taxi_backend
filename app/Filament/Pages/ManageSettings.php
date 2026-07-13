<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'الإعدادات';
    protected static ?string $navigationLabel = 'إعدادات النظام';
    protected static ?int $navigationSort = 99;
    protected static string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'إعدادات النظام';
    }

    public function mount(): void
    {
        $this->form->fill([
            'commission_percentage' => Setting::get('commission_percentage', 10),
            'app_currency'          => Setting::get('app_currency', 'SYP'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('commission_percentage')
                    ->label('نسبة عمولة الشركة (%)')
                    ->helperText('النسبة التي تقتطعها الشركة من كل رحلة مكتملة')
                    ->numeric()->required()->minValue(0)->maxValue(100)->suffix('%'),
                TextInput::make('app_currency')
                    ->label('عملة التطبيق')
                    ->required()->maxLength(10),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('commission_percentage', $data['commission_percentage'], 'decimal');
        Setting::set('app_currency', $data['app_currency'], 'string');

        Notification::make()->title('تم حفظ الإعدادات بنجاح')->success()->send();
    }
}
