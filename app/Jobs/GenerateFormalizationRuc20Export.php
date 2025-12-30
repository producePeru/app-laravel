<?php

namespace App\Jobs;

use App\Exports\FormalizationRuc20ExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Formalization20;

use App\Mail\AdvisoriesExportReadyMail;
use App\Mail\FormalizationRuc20ExportReadyMail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class GenerateFormalizationRuc20Export implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $filters;
    public string $filename;
    public int $userId;
    public string $type;
    public string $email;

    public function __construct(array $filters, string $filename, int $userId, string $type = 'xlsx', ?string $email = null)
    {
        $this->filters = $filters;
        $this->filename = $filename;
        $this->userId = $userId;
        $this->type = $type;
        $this->email = $email ?? 'digitalizacion.pnte@gmail.com';
    }

    public function handle(FormalizationRuc20ExportService $service)
    {
        try {
            $query = Formalization20::query()->with([
                'city:id,name',
                'province:id,name',
                'district:id,name',
                'modality:id,name',
                'comercialactivity:id,name',
                'regime:id,name',
                'notary:id,name',
                'economicsector:id,name',
                'typecapital:id,name',
                'mype:id,name,ruc',
                'people:id,documentnumber,birthday,lastname,middlename,name,gender_id,country_id,typedocument_id,sick,hasSoon,phone,email',
                'people.gender:id,name',
                'people.typedocument:id,avr',
                'sede',
                'user:id,name,lastname,middlename',
                'userupdater:id,name,lastname,middlename'
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
            Log::error('Error generando export RUC20: ' . $e->getMessage(), ['filters' => $this->filters]);
            throw $e;
        }
    }
}
