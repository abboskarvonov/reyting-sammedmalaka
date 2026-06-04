<?php

namespace App\Filament\Resources\Tasks\RelationManagers;

use App\Models\Teacher;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TeacherAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    protected static ?string $title = "O'qituvchilar";

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('teacher_id')
                ->label("O'qituvchi")
                ->options(function () {
                    $alreadyAssigned = $this->ownerRecord
                        ->assignments()
                        ->pluck('teacher_id');

                    return Teacher::active()
                        ->whereNotIn('id', $alreadyAssigned)
                        ->with('user')
                        ->get()
                        ->mapWithKeys(fn ($t) => [
                            $t->id => $t->user->name
                                . ($t->position ? ' — ' . $t->position : ''),
                        ]);
                })
                ->searchable()
                ->required()
                ->helperText("Allaqachon biriktirilgan o'qituvchilar ro'yxatda ko'rsatilmaydi"),

            Textarea::make('note')
                ->label('Izoh (ixtiyoriy)')
                ->rows(2)
                ->default(null),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('teacher.user.name')
                    ->label("O'qituvchi")
                    ->searchable()
                    ->sortable(),

                TextColumn::make('teacher.position')
                    ->label('Lavozim')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Holat')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'completed' ? 'Bajardi ✓' : 'Bajarmadi')
                    ->color(fn ($state) => $state === 'completed' ? 'success' : 'danger'),

                TextColumn::make('completed_at')
                    ->label('Bajargan vaqti')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('note')
                    ->label('Izoh')
                    ->placeholder('—')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Holat')
                    ->options([
                        'pending'   => 'Bajarmadi',
                        'completed' => 'Bajardi',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label("O'qituvchi biriktirish")
                    ->mutateFormDataUsing(fn (array $data) => array_merge($data, [
                        'status'       => 'pending',
                        'completed_at' => null,
                    ])),
            ])
            ->actions([
                // Bajardi / Bajarmadi toggle
                Action::make('toggle_status')
                    ->label(fn ($record) => $record->isCompleted() ? 'Bajarmadi deb belgilash' : 'Bajardi deb belgilash')
                    ->icon(fn ($record) => $record->isCompleted() ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->isCompleted() ? 'danger' : 'success')
                    ->requiresConfirmation(fn ($record) => $record->isCompleted())
                    ->modalHeading("Holatni o'zgartirish")
                    ->modalDescription(fn ($record) => $record->isCompleted()
                        ? "Bu o'qituvchini 'bajarmadi' holatiga qaytarasizmi?"
                        : null
                    )
                    ->form(fn ($record) => $record->isCompleted() ? [] : [
                        Textarea::make('note')
                            ->label('Izoh (ixtiyoriy)')
                            ->rows(2)
                            ->default($record->note),
                    ])
                    ->action(function ($record, array $data) {
                        if ($record->isCompleted()) {
                            $record->markPending();
                            Notification::make()
                                ->title("'Bajarmadi' deb belgilandi")
                                ->warning()
                                ->send();
                        } else {
                            $record->markCompleted($data['note'] ?? null);
                            Notification::make()
                                ->title("'Bajardi' deb belgilandi ✓")
                                ->success()
                                ->send();
                        }
                    }),

                DeleteAction::make()
                    ->label('Olib tashlash'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->label('Tanlanganlarni olib tashlash'),
            ])
            ->defaultSort('status')
            ->emptyStateHeading("Hali o'qituvchi biriktirilmagan")
            ->emptyStateDescription("'O'qituvchi biriktirish' tugmasi orqali qo'shing");
    }
}
