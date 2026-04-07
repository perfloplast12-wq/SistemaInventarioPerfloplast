<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InjectionReportResource\Pages;
use App\Filament\Resources\InjectionReportResource\RelationManagers;
use App\Models\InjectionReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InjectionReportResource extends Resource
{
    protected static ?string $model = InjectionReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $navigationLabel = 'Reportes de Inyección';
    
    protected static ?string $modelLabel = 'Reporte de Inyección';
    
    protected static ?string $pluralModelLabel = 'Reportes de Inyección';

    protected static ?string $navigationGroup = 'Mantenimiento';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles del Reporte')
                    ->schema([
                        Forms\Components\DatePicker::make('fecha')
                            ->required()
                            ->default(now()),
                        Forms\Components\TextInput::make('turno_horario')
                            ->label('Turno / Horario')
                            ->placeholder('Ej: TURNO -- 2 -- 12:00 PM A 12:00 AM')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nombre_empleado')
                            ->label('Nombre del Empleado')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('maquina')
                            ->label('Máquina')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('producto')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('producto_por_color')
                            ->label('Producto por Color')
                            ->placeholder('Ej: 350 naranja, 250 azul, 150 verde')
                            ->columnSpanFull(),
                    ])->columns(3),
                
                Forms\Components\Section::make('Cantidades')
                    ->schema([
                        Forms\Components\TextInput::make('total')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('rechazo')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('sacos_usados')
                            ->label('Sacos Usados')
                            ->required()
                            ->numeric()
                            ->default(0),
                    ])->columns(3),

                Forms\Components\Section::make('Opcional')
                    ->schema([
                        Forms\Components\Textarea::make('observaciones')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('turno_horario')
                    ->label('Turno / Horario')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombre_empleado')
                    ->label('Empleado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('maquina')
                    ->searchable(),
                Tables\Columns\TextColumn::make('producto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('producto_por_color')
                    ->label('Colores')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rechazo')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sacos_usados')
                    ->label('Sacos Usados')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('observaciones')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['admin', 'super_admin', 'mantenimiento']) ?? false;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInjectionReports::route('/'),
            'create' => Pages\CreateInjectionReport::route('/create'),
            'edit' => Pages\EditInjectionReport::route('/{record}/edit'),
        ];
    }
}
