<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Filament\Resources\SubscriptionResource\RelationManagers;
use App\Models\Subscription;
use App\Interfaces\PaymentGatewayInterface;
use Faker\Core\Color;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $modelLabel = 'Assinatura';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('gateway_id')
                    ->label('ID Gateway')
                    ->searchable()
                    ->copyable()
                    ->color('gray')
                    ->limit(20)
                    ->fontFamily('mono'),            
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plano')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'inactive', 'expired' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray'
                    }),
                Tables\Columns\TextColumn::make('locked_price')
                    ->label('Valor')
                    ->money('BRL', divideBy: 100),
                Tables\Columns\IconColumn::make('auto_renew')
                    ->label('Renova?')
                    ->boolean(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Vence em')
                    ->dateTime('d/m/Y'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Ativo',
                        'pending' => 'Pendente',
                        'inactive' => 'Inativo',
                        'expired' => 'Expirado',
                        'cancelled' => 'Cancelado',
                    ]),
                Tables\Filters\SelectFilter::make('plan')
                    ->label('Plano')
                    ->relationship('plan', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Subscription $record) => $record->status === 'active')
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar Assinatura')
                    ->modalDescription('Tem certeza? O acesso do usuário será interrompido imediatamente.')
                    ->action(function (Subscription $record, PaymentGatewayInterface $paymentGateway) {
                        try {
                            $paymentGateway->cancelSubscription($record);       

                            $record->update([
                                'status' => 'cancelled',
                                'ends_at' => now(),
                                'auto_renew' => false,
                            ]);

                            Notification::make()
                                ->title('Assinatura cancelada')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Falha ao cancelar assinatura')
                                ->body($e->getMessage())
                                ->danger()
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
            'index' => Pages\ListSubscriptions::route('/'),
        ];
    }
}
