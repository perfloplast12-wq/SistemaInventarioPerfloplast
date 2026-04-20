<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InjectionReportResource\Pages;
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

    protected static ?string $navigationLabel = 'Reportes de Actividad';

    protected static ?string $modelLabel = 'Reporte de Actividad';

    protected static ?string $pluralModelLabel = 'Reportes de Actividad';

    protected static ?string $navigationGroup = 'Mantenimiento';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('employee_name')
                    ->label('Nombre del Empleado')
                    ->required()
                    ->maxLength(255),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee_name')
                    ->label('Empleado')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('Puesto')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('department')
                    ->label('Departamento')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('week_range')
                    ->label('Semana')
                    ->searchable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Días Registrados')
                    ->counts('items')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->url(fn (InjectionReport $record) => url('/admin/injection-reports/'.$record->id.'/pdf'))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        
        if (! $user) {
            return false;
        }

        if (! method_exists($user, 'hasRole')) {
            return $user->is_active ?? false;
        }

        try {
            return $user->hasRole(['admin', 'super_admin', 'mantenimiento', 'warehouse', 'viewer']);
        } catch (\Throwable $e) {
            return $user->is_active ?? false;
        }
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInjectionReports::route('/'),
            'create' => Pages\CreateInjectionReport::route('/create'),
            'edit' => Pages\EditInjectionReport::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
