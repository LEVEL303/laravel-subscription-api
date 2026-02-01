<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Models\Plan;
use App\Filament\Resources\PlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->action(function (Plan $record) {
                    if ($record->subscriptions()->exists()) {
                        $record->update(['status' => 'inactive']);

                        Notification::make()
                            ->title('Plano inativado')
                            ->body('Este plano possui assinaturas vinculadas. Ele foi marcado como "Inativo" para preservar o histórico.')
                            ->warning()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('index'));

                    } else {
                        $record->delete();
                        
                        Notification::make()
                            ->title('Plano excluído')
                            ->success()
                            ->send();
                            
                        $this->redirect($this->getResource()::getUrl('index'));
                    }
                }),
        ];
    }
}
