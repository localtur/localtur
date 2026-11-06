<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReclamoResource\Pages;
use App\Models\Reclamo;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** "¿Es tu negocio?": solicitudes de dueños para tomar un aviso sin activar. */
class ReclamoResource extends Resource
{
    protected static ?string $model = Reclamo::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-raised';

    protected static ?string $modelLabel = 'solicitud';

    protected static ?string $pluralModelLabel = 'solicitudes de dueños';

    protected static ?string $navigationLabel = 'Solicitudes';

    public static function getNavigationBadge(): ?string
    {
        $n = Reclamo::where('estado', 'pendiente')->count();

        return $n ? (string) $n : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($q) => $q->with(['aviso.localidad', 'aviso.privado', 'usuario']))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->since()->sortable(),
                Tables\Columns\TextColumn::make('aviso.titulo')->label('Aviso')->searchable()->limit(35)
                    ->description(fn (Reclamo $r) => $r->aviso?->localidad?->nombre),
                Tables\Columns\TextColumn::make('usuario.name')->label('Solicitante')->searchable()
                    ->description(fn (Reclamo $r) => $r->usuario?->email),
                Tables\Columns\TextColumn::make('relacion')->label('Relación')->formatStateUsing(fn ($s) => Reclamo::RELACIONES[$s] ?? $s),
                Tables\Columns\TextColumn::make('telefono')->label('Tel. declarado')
                    ->description(fn (Reclamo $r) => 'Registro: '.($r->aviso?->privado?->telefono ?: '—')),
                Tables\Columns\TextColumn::make('mensaje')->limit(50)->toggleable(),
                Tables\Columns\TextColumn::make('estado')->badge()
                    ->formatStateUsing(fn ($s) => Reclamo::ESTADOS[$s] ?? $s)
                    ->color(fn ($s) => ['pendiente' => 'warning', 'aprobado' => 'success', 'rechazado' => 'danger'][$s] ?? 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')->options(Reclamo::ESTADOS)->default('pendiente'),
            ])
            ->actions([
                Tables\Actions\Action::make('aprobar')
                    ->label('Aprobar')->icon('heroicon-o-check')->color('success')
                    ->visible(fn (Reclamo $r) => $r->estado === 'pendiente')
                    ->requiresConfirmation()
                    ->modalDescription('El aviso pasa a esta persona y queda con la activación gratis.')
                    ->action(function (Reclamo $r) {
                        $r->aprobar(auth()->user());
                        Notification::make()->title('Solicitud aprobada')->success()->send();
                    }),
                Tables\Actions\Action::make('rechazar')
                    ->label('Rechazar')->icon('heroicon-o-x-mark')->color('danger')
                    ->visible(fn (Reclamo $r) => $r->estado === 'pendiente')
                    ->form([Forms\Components\TextInput::make('motivo')->label('Motivo (lo verá el solicitante)')->maxLength(190)])
                    ->action(fn (Reclamo $r, array $data) => $r->rechazar(auth()->user(), $data['motivo'] ?? null)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListReclamos::route('/')];
    }
}
