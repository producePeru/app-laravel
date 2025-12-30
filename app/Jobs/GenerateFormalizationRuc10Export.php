<?php

namespace App\Jobs;

use App\Exports\FormalizationRuc10ExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Formalization10;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdvisoriesExportReadyMail;

class GenerateFormalizationRuc10Export implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $filters;
    public string $filename;
    public int $userId;
    public string $type;
    public string $email;

    public $timeout = 600; // 10 minutos
    public $tries = 3;
    public $maxExceptions = 3;

    public function __construct(array $filters, string $filename, int $userId, string $type = 'xlsx', ?string $email = null)
    {
        $this->filters = $filters;
        $this->filename = $filename;
        $this->userId = $userId;
        $this->type = $type;
        $this->email = $email ?? 'digitalizacion.pnte@gmail.com';
    }

    public function handle(FormalizationRuc10ExportService $service)
    {
        try {
            $query = Formalization10::query()->with([
                'city:id,name',
                'comercialactivity:id,name',
                'detailprocedure:id,name',
                'district:id,name',
                'economicsector:id,name',
                'modality:id,name',
                'people:id,documentnumber,birthday,lastname,middlename,name,gender_id,country_id,typedocument_id,sick,hasSoon,phone,email',
                'people.gender:id,name',
                'people.pais:id,name',
                'people.typedocument:id,avr',
                'province:id,name',
                'sede',
                'user:id,name,lastname,middlename'
            ]);

            if (!empty($this->filters['year'])) {
                $query->whereYear('created_at', $this->filters['year']);
            }

            $pathFile = public_path('exports/' . $this->filename);

            $service->generateFromQuery($query, $pathFile, $this->type);

            Mail::mailer('hostinger')->to($this->email)
                ->send(new AdvisoriesExportReadyMail($this->filename, $pathFile));

            Log::info("Archivo {$this->filename} generado y enviado a {$this->email}");
        } catch (\Throwable $e) {
            Log::error('Error generando export RUC10: ' . $e->getMessage(), ['filters' => $this->filters]);
            throw $e;
        }
    }
}
