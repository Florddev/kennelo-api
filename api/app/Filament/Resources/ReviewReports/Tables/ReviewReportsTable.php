<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReviewReports\Tables;

use App\Enums\ReviewReportReasonEnum;
use App\Enums\ReviewReportStatusEnum;
use App\Models\ReviewReport;
use App\Services\Review\ReviewReportService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReviewReportsTable
{
    /**
     * @return array<string, string>
     */
    public static function reasonLabels(): array
    {
        return [
            ReviewReportReasonEnum::INAPPROPRIATE->value => 'Inapproprié',
            ReviewReportReasonEnum::OFFENSIVE->value => 'Offensant',
            ReviewReportReasonEnum::FAKE->value => 'Faux avis',
            ReviewReportReasonEnum::SPAM->value => 'Spam',
            ReviewReportReasonEnum::OTHER->value => 'Autre',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            ReviewReportStatusEnum::PENDING->value => 'En attente',
            ReviewReportStatusEnum::REVIEWED->value => 'Traité',
            ReviewReportStatusEnum::REJECTED->value => 'Rejeté',
            ReviewReportStatusEnum::REMOVED->value => 'Avis supprimé',
        ];
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'reporter' => fn ($q) => $q->withoutGlobalScope('active')->withTrashed(),
            ]))
            ->columns([
                TextColumn::make('reason')
                    ->label('Motif')
                    ->badge()
                    ->formatStateUsing(fn (ReviewReportReasonEnum $state): string => self::reasonLabels()[$state->value] ?? $state->value),
                TextColumn::make('description')
                    ->label('Description')
                    ->placeholder('—')
                    ->wrap()
                    ->limit(60),
                TextColumn::make('reporter.email')
                    ->label('Signalé par')
                    ->getStateUsing(fn (ReviewReport $record): string => $record->reporter !== null
                        ? trim($record->reporter->first_name.' '.$record->reporter->last_name)
                        : '—'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (ReviewReportStatusEnum $state): string => self::statusLabels()[$state->value] ?? $state->value)
                    ->color(fn (ReviewReportStatusEnum $state): string => match ($state) {
                        ReviewReportStatusEnum::PENDING => 'warning',
                        ReviewReportStatusEnum::REVIEWED => 'success',
                        ReviewReportStatusEnum::REJECTED => 'gray',
                        ReviewReportStatusEnum::REMOVED => 'danger',
                    }),
                TextColumn::make('created_at')
                    ->label('Signalé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(self::statusLabels()),
            ])
            ->recordActions([
                Action::make('resolve')
                    ->label('Résoudre')
                    ->icon(Heroicon::CheckCircle)
                    ->color('primary')
                    ->visible(fn (): bool => auth()->user()?->can('manage', ReviewReport::class) ?? false)
                    ->fillForm(fn (ReviewReport $record): array => ['status' => $record->status->value])
                    ->schema([
                        Select::make('status')
                            ->label('Décision')
                            ->required()
                            ->options(self::statusLabels()),
                    ])
                    ->action(function (ReviewReport $record, array $data): void {
                        app(ReviewReportService::class)->updateStatus($record, $data['status']);

                        Notification::make()
                            ->title('Signalement traité')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
