<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Filament\Resources\PlanResource\RelationManagers;
use App\Models\Plan;
use App\Models\Feature;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Plano';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(191)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->readOnly(),
                Forms\Components\TextInput::make('price')
                    ->label('Preço (R$)')
                    ->required()
                    ->numeric()
                    ->prefix('R$')
                    ->minValue(0)
                    ->formatStateUsing(fn ($state) => $state / 100)
                    ->dehydrateStateUsing(fn ($state) => $state * 100),
                Forms\Components\TextInput::make('trial_days')
                    ->label('Dias de teste')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                Forms\Components\Select::make('period')
                    ->label('Período')
                    ->options([
                        'monthly' => 'Mensal',
                        'yearly' => 'Anual',
                    ])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Ativo',
                        'inactive' => 'Inativo',
                    ])
                    ->default('active'),
                Forms\Components\Textarea::make('description')
                    ->label('Descrição')
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Preço')
                    ->money('BRL', divideBy: 100),
                Tables\Columns\TextColumn::make('trial_days')->label('Dias de teste'),
                Tables\Columns\TextColumn::make('period')
                    ->label('Período')
                    ->formatStateUsing(fn ($state) => $state === 'monthly' ? 'Mensal' : 'Anual'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('features_count')
                    ->counts('features')
                    ->label('Funcionalidades')
                    ->badge(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('period')
                    ->label('Perído')
                    ->options([
                        'monthly' => 'Mensal',
                        'yearly' => "Anual",
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Ativo',
                        'inactive' => 'Inativo',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('bind_feature')
                    ->label('Vincular')
                    ->icon('heroicon-m-plus-circle')
                    ->color('success')
                    ->modalHeading('Vincular Funcionalidades')
                    ->modalDescription('Selecione as funcionalidades que deseja vincular a este plano.')
                    ->form(function (Plan $record) {
                        $existingIds = $record->features()->pluck('features.id')->toArray();

                        return [
                            Forms\Components\CheckboxList::make('features_ids')
                                ->label('Funcionalidades Diponíveis')
                                ->options(
                                    Feature::whereNotIn('id', $existingIds)->pluck('name', 'id')
                                )
                                ->searchable()
                                ->bulkToggleable()
                                ->columns(2)
                                ->required(),
                        ];
                    })
                    ->action(function (Plan $record, array $data) {
                        $record->features()->syncWithoutDetaching($data['features_ids']);

                        Notification::make()
                            ->title('Funcionalidades vinculadas!')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('unlink_feature')
                    ->label('Desvincular')
                    ->icon('heroicon-m-minus-circle')
                    ->color('danger')
                    ->modalHeading('Desvincular Funcionalidades')
                    ->modalDescription('Selecione as funcionalidades que deseja desvincular deste plano.')
                    ->form(fn (Plan $record) => [
                        Forms\Components\CheckboxList::make('features_id')
                            ->label('Funcionalidades vinculadas')
                            ->options(
                                $record->features()->pluck('name', 'features.id')
                            )
                            ->searchable()
                            ->columns(2)
                            ->required(),
                    ])
                    ->action(function (Plan $record, array $data) {
                        $record->features()->detach($data['features_id']);
                        
                        Notification::make()
                            ->title('Funcionalidades desvinculadas')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->action(function (Plan $record) {
                        if ($record->subscriptions()->exists()) {
                            $record->update(['status' => 'inactive']);

                            Notification::make()
                                ->title('Plano inativado')
                                ->body('Este plano possui assinaturas vinculadas. Ele foi marcado como "Inativo" para preservar o histórico.')
                                ->warning()
                                ->send();

                        } else {
                            $record->delete();

                            Notification::make()
                                ->title('Plano excluído')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([]);
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
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
