<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AvisoResource\Pages;
use App\Models\Aviso;
use App\Models\Comuna;
use App\Models\Region;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AvisoResource extends Resource
{
    protected static ?string $model = Aviso::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $modelLabel = 'aviso';

    protected static ?string $pluralModelLabel = 'avisos';

    protected static ?string $recordTitleAttribute = 'titulo';

    private static function categorias(): array
    {
        return collect(config('marca.categorias'))->mapWithKeys(fn ($c, $k) => [$k => $c['nombre']])->all();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Aviso público')
                ->description('Todo lo de esta sección se muestra en el sitio.')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('titulo')->required()->maxLength(120),
                    Forms\Components\TextInput::make('slug')->required()->maxLength(255),
                    Forms\Components\Select::make('categoria')->options(self::categorias())->required(),
                    Forms\Components\TextInput::make('subtipo'),
                    Forms\Components\Select::make('localidad_id')
                        ->relationship('localidad', 'nombre')->searchable()->preload()->required(),
                    Forms\Components\Select::make('estado')->options(Aviso::ESTADOS)->required(),
                    Forms\Components\TextInput::make('descripcion_corta')->maxLength(160)->columnSpanFull(),
                    Forms\Components\Textarea::make('descripcion')->rows(6)->columnSpanFull(),
                    Forms\Components\TextInput::make('direccion')->columnSpanFull(),
                    Forms\Components\TextInput::make('lat')->numeric(),
                    Forms\Components\TextInput::make('lng')->numeric(),
                    Forms\Components\Toggle::make('ubicacion_aproximada'),
                    Forms\Components\Toggle::make('indexable')->helperText('Solo cuando la ubicación ya está confirmada.'),
                ]),
            Forms\Components\Section::make('Fotos')
                ->description('Hasta 10. La primera es la portada; arrastra para cambiar el orden. Sin fotos, el aviso muestra ilustraciones referenciales.')
                ->schema([
                    Forms\Components\FileUpload::make('fotos')
                        ->hiddenLabel()
                        ->image()
                        ->multiple()
                        ->maxFiles(10)
                        ->maxSize(8192)
                        ->reorderable()
                        ->appendFiles()
                        ->panelLayout('grid')
                        ->disk('public')
                        ->directory('avisos')
                        ->visibility('public')
                        ->imageResizeMode('contain')
                        ->imageResizeTargetWidth('1600')
                        ->imageResizeTargetHeight('1600')
                        ->imageEditor(),
                ]),
            Forms\Components\Section::make('Datos privados')
                ->description('Nunca se muestran en el sitio. Solo para el equipo.')
                ->relationship('privado')
                ->collapsed()
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('nombre_original')->disabled(),
                    Forms\Components\TextInput::make('clase_original')->disabled(),
                    Forms\Components\TextInput::make('razon_social'),
                    Forms\Components\TextInput::make('rut'),
                    Forms\Components\TextInput::make('telefono'),
                    Forms\Components\TextInput::make('estado_telefono'),
                    Forms\Components\TextInput::make('correo')->email(),
                    Forms\Components\TextInput::make('web'),
                    Forms\Components\Toggle::make('no_contactar')->helperText('Pidió no ser contactado.'),
                    Forms\Components\Textarea::make('notas')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')->searchable()->sortable()->limit(40),
                Tables\Columns\TextColumn::make('categoria')
                    ->formatStateUsing(fn ($state) => self::categorias()[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('subtipo')->toggleable(),
                Tables\Columns\TextColumn::make('localidad.nombre')->label('Localidad')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('localidad.comuna.nombre')->label('Comuna')->toggleable(),
                Tables\Columns\TextColumn::make('estado')
                    ->formatStateUsing(fn ($state) => Aviso::ESTADOS[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'sin_activar' => 'gray', 'pausado' => 'danger', 'basico' => 'warning', default => 'success',
                    }),
                Tables\Columns\TextColumn::make('privado.estado_telefono')->label('Teléfono')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('indexable')->boolean()->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Actualizado')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categoria')->options(self::categorias()),
                Tables\Filters\SelectFilter::make('estado')->options(Aviso::ESTADOS),
                Tables\Filters\SelectFilter::make('region')
                    ->label('Región')
                    ->options(fn () => Region::orderBy('orden')->orderBy('nombre')->pluck('nombre', 'id'))
                    ->query(fn (Builder $q, array $data) => $q->when($data['value'] ?? null,
                        fn ($q, $id) => $q->whereHas('localidad.comuna', fn ($c) => $c->where('region_id', $id)))),
                Tables\Filters\SelectFilter::make('comuna')
                    ->label('Comuna')
                    ->searchable()
                    ->options(fn () => Comuna::orderBy('nombre')->pluck('nombre', 'id'))
                    ->query(fn (Builder $q, array $data) => $q->when($data['value'] ?? null,
                        fn ($q, $id) => $q->whereHas('localidad', fn ($l) => $l->where('comuna_id', $id)))),
                Tables\Filters\Filter::make('con_algo_por_revisar')
                    ->label('Con algo por revisar')
                    ->query(fn (Builder $q) => $q->whereHas('privado', fn ($p) => $p->whereNotNull('notas'))),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Aviso $r) => $r->url, shouldOpenInNewTab: true),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('pausar')
                    ->label('Pausar')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['estado' => 'pausado'])),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAvisos::route('/'),
            'edit' => Pages\EditAviso::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['localidad.comuna', 'privado']);
    }
}
