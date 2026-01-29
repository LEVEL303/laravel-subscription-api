<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebhookLogResource\Pages;
use App\Filament\Resources\WebhookLogResource\RelationManagers;
use App\Models\WebhookLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WebhookLogResource extends Resource
{
    protected static ?string $model = WebhookLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $modelLabel = 'Logs de Webhook';

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
                Tables\Columns\TextColumn::make('gateway_event_id')
                    ->label('ID do Evento')
                    ->searchable()
                    ->copyable()
                    ->color('gray')
                    ->limit(20)
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('event_type')
                    ->label('Tipo de Evento')
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'processed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ocorrido em')
                    ->dateTime('d/m/Y H:m:s'),
                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Processado em')
                    ->dateTime('d/m/Y H:m:s'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'processed' => 'Processado',
                        'pending' => 'Pendente',
                        'failed' => 'Falhou',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('payload')
                    ->label('JSON')
                    ->icon('heroicon-o-code-bracket')
                    ->color('info')
                    ->modalHeading('Payload do Evento')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn ($action) => $action->label('Fechar'))
                    ->form(fn (WebhookLog $record) => [
                        Forms\Components\Textarea::make('payload')
                            ->label('Conteúdo Recebido')
                            ->default(json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                            ->rows(20)
                            ->readOnly()
                            ->extraAttributes(['class' => 'font-mono text-xs']),
                    ]),
                
                Tables\Actions\Action::make('error')
                    ->label('Erro')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn (WebhookLog $record) => !empty($record->error_message))
                    ->modalHeading('Detalhes do erro')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn ($action) => $action->label('Fechar'))
                    ->form(fn (WebhookLog $record) => [
                        Forms\Components\Textarea::make('error')
                            ->default($record->error_message)
                            ->rows(10)
                            ->readOnly(),
                    ]),
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
            'index' => Pages\ListWebhookLogs::route('/'),
        ];
    }
}
