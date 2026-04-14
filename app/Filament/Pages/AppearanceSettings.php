<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class AppearanceSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static string $view = 'filament.pages.appearance-settings';

    protected static ?string $navigationLabel = 'Apariencia';

    protected static ?string $title = 'Ajustes de Apariencia';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::whereIn('key', [
            'primary_color_1',
            'primary_color_2',
            'sidebar_width',
            'border_radius',
            'glass_effect',
            'mesh_background',
            'button_layout',
            'shadow_depth',
            'bg_color',
            'sidebar_bg_color',
            'card_bg_color',
            'text_color',
            'theme_preset'
        ])->pluck('value', 'key')->toArray();

        $this->form->fill([
            'primary_color_1' => $settings['primary_color_1'] ?? '#6366f1',
            'primary_color_2' => $settings['primary_color_2'] ?? '#3b82f6',
            'sidebar_width' => $settings['sidebar_width'] ?? 17,
            'border_radius' => $settings['border_radius'] ?? 12,
            'glass_effect' => (bool)($settings['glass_effect'] ?? false),
            'mesh_background' => (bool)($settings['mesh_background'] ?? true),
            'button_layout' => $settings['button_layout'] ?? 'pill',
            'shadow_depth' => $settings['shadow_depth'] ?? 'deep',
            'bg_color' => $settings['bg_color'] ?? '#f8fafc',
            'sidebar_bg_color' => $settings['sidebar_bg_color'] ?? '#ffffff',
            'card_bg_color' => $settings['card_bg_color'] ?? '#ffffff',
            'text_color' => $settings['text_color'] ?? '#1e293b',
            'theme_preset' => $settings['theme_preset'] ?? 'custom',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Tabs::make('Appearance Settings')
                    ->tabs([
                        \Filament\Forms\Components\Tabs\Tab::make('🚀 Estilos Exquisitos')
                            ->icon('heroicon-o-rocket-launch')
                            ->schema([
                                Section::make('🎭 Galería de Temas Maestro')
                                    ->description('Selecciona una de nuestras paletas exclusivas. El sistema equilibrará automáticamente los contrastes en modo luz y sombra.')
                                    ->schema([
                                        \Filament\Forms\Components\Select::make('theme_preset')
                                            ->label('Elegir Estilo')
                                            ->options([
                                                'midnight' => '🔮 Violeta Real (Nocturno)',
                                                'earth' => '🪵 Tierra Profunda (Cálido)',
                                                'peach' => '🍑 Durazno Suave (Vibrante)',
                                                'emerald' => '🌿 Bosque Esmeralda (Natural)',
                                                'custom' => '🎨 Clave Indigo (Estándar)',
                                            ])
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state === 'midnight') {
                                                    $set('primary_color_1', '#7B45F0');
                                                    $set('primary_color_2', '#4D1AB1');
                                                    $set('bg_color', '#D0BCFC'); // Lavanda Claro (Top)
                                                    $set('sidebar_bg_color', '#ffffff');
                                                    $set('card_bg_color', '#ffffff');
                                                    $set('text_color', '#1e293b');
                                                    $set('glass_effect', true);
                                                    $set('mesh_background', true);
                                                    $set('shadow_depth', 'deep');
                                                } elseif ($state === 'earth') {
                                                    $set('primary_color_1', '#8C6E63');
                                                    $set('primary_color_2', '#5D4037');
                                                    $set('bg_color', '#FFF2DF'); // Crema Claro (Top)
                                                    $set('sidebar_bg_color', '#ffffff');
                                                    $set('card_bg_color', '#ffffff');
                                                    $set('text_color', '#1B1010');
                                                    $set('glass_effect', true);
                                                    $set('mesh_background', true);
                                                    $set('shadow_depth', 'medium');
                                                } elseif ($state === 'peach') {
                                                    $set('primary_color_1', '#FEA18E');
                                                    $set('primary_color_2', '#D35400');
                                                    $set('bg_color', '#FFF9F5'); // Durazno Pálido (Top)
                                                    $set('sidebar_bg_color', '#ffffff');
                                                    $set('card_bg_color', '#ffffff');
                                                    $set('text_color', '#78350f');
                                                    $set('glass_effect', true);
                                                    $set('mesh_background', true);
                                                    $set('shadow_depth', 'deep');
                                                } elseif ($state === 'emerald') {
                                                    $set('primary_color_1', '#576B34');
                                                    $set('primary_color_2', '#2E3B1B');
                                                    $set('bg_color', '#F2F9F5'); // Menta Suave (Top)
                                                    $set('sidebar_bg_color', '#ffffff');
                                                    $set('card_bg_color', '#ffffff');
                                                    $set('text_color', '#072A1D');
                                                    $set('glass_effect', true);
                                                    $set('mesh_background', true);
                                                    $set('shadow_depth', 'medium');
                                                } else { // Custom/Indigo standard
                                                    $set('primary_color_1', '#6366f1');
                                                    $set('primary_color_2', '#312e81');
                                                    $set('bg_color', '#f8fafc');
                                                    $set('sidebar_bg_color', '#ffffff');
                                                    $set('card_bg_color', '#ffffff');
                                                    $set('text_color', '#1e293b');
                                                    $set('glass_effect', true);
                                                    $set('mesh_background', true);
                                                    $set('shadow_depth', 'medium');
                                                }
                                             }),
                                        
                                        // Hidden fields to persist theme-derived colors
                                        Hidden::make('primary_color_1'),
                                        Hidden::make('primary_color_2'),
                                        Hidden::make('bg_color'),
                                        Hidden::make('sidebar_bg_color'),
                                        Hidden::make('card_bg_color'),
                                        Hidden::make('text_color'),
                                    ]),
                            ]),

                        \Filament\Forms\Components\Tabs\Tab::make('✨ Acabados e Interfaz')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Section::make('Efectos Premium')
                                    ->columns(3)
                                    ->schema([
                                        \Filament\Forms\Components\Toggle::make('mesh_background')->label('Mesh Gradient')->default(true),
                                        \Filament\Forms\Components\Toggle::make('glass_effect')->label('Efecto Glass')->default(true),
                                        \Filament\Forms\Components\Select::make('shadow_depth')
                                            ->label('Sombras')
                                            ->options(['none' => 'Plano','soft' => 'Suave','medium' => 'Moderno','deep' => 'Elevado']),
                                    ]),
                                Section::make('Geometría y Forma')
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('sidebar_width')->label('Ancho Side (rem)')->numeric()->extraAttributes(['min' => 12, 'max' => 20]),
                                        TextInput::make('border_radius')->label('Rounding (px)')->numeric()->extraAttributes(['min' => 0, 'max' => 24]),
                                        \Filament\Forms\Components\Select::make('button_layout')
                                            ->label('Botones')
                                            ->options(['square' => 'Rectos','rounded' => 'Redondeados','pill' => 'Cápsula']),
                                    ]),
                            ]),
                    ])
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar Cambios')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget('appearance_settings');

        Notification::make()
            ->title('Ajustes guardados correctamente')
            ->success()
            ->send();
            
        // Forzar recompilación o refresco visual si es necesario
    }
}
