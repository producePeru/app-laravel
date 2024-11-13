<?php

namespace App\Http\Controllers\Download;

use App\Exports\FairParticipantsExport;
use App\Http\Controllers\Controller;
use App\Models\Fair;
use App\Models\FairPostulate;
use Carbon\Carbon;
Carbon::setLocale('es');
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DownloadFairParticipantsController extends Controller
{
    public function exportFairParticipants($slugFair)
    {
        $fair = Fair::where('slug', $slugFair)->first();

        if (!$fair) {
            return response()->json(['message' => 'Fair not found'], 404);
        }

        $query = FairPostulate::with([
            'fair',
            'mype',
            'mype.region:id,name',
            'mype.province:id,name',
            'mype.district:id,name',
            'mype.category:id,name',
            'person',
            'person.pais:id,name',
            'person.city:id,name',
            'person.province:id,name',
            'person.district:id,name',
            'person.typedocument:id,name',
            'person.gender:id,name'
        ])
            ->where('fair_id', $fair->id)
            ->orderBy('created_at', 'desc');

        $data = $query->get();

        $result = $data->map(function ($item, $index) {
            return [
                'index' => $index + 1,
                // 'fair_name' => $item->fair->title,
                'status' => $item->status == 1 ? 'PARTICIPA' : '✖',
                // 'email_send' => $item->email,
                'created_at' => Carbon::parse($item->created_at)->format('d-m-Y'),
                'ruc' => $item->mype->ruc,
                'comercialName' => $item->mype->comercialName,
                'socialReason' => $item->mype->socialReason,
                'businessSector' => $item->mype->category->name,
                'percentageOwnPlan' => $item->mype->percentageOwnPlan,
                'percentageMaquila' => $item->mype->percentageMaquila,
                'capacityProdMounth' => $item->mype->capacityProdMounth,
                'isGremio' => $item->mype->isGremio,
                'nameGremio' => $item->mype->nameGremio,
                'pointSale' => $item->mype->pointSale,
                'numberPointSale' => $item->mype->numberPointSale,
                // 'actividadEconomica' => $item->mype->actividadEconomica,
                'mype_city' => $item->mype->region->name,
                'mype_province' => $item->mype->province->name,
                'mype_district' => $item->mype->district->name,
                'mype_address' => $item->mype->address,
                'web' => $item->mype->web,
                'facebook' => $item->mype->facebook,
                'instagram' => $item->mype->instagram,
                'description' => $item->mype->description,

                // 'filePDF_name' => $item->mype->filePDF_name,
                // 'filePDF_url' => $item->mype->filePDF_path ? asset($item->mype->filePDF_path) : null,
                // 'logo_name' => $item->mype->logo_name,
                // 'logo_url' => $item->mype->logo_path ? asset($item->mype->logo_path) : null,
                // 'img1_name' => $item->mype->img1_name,
                // 'img1_url' => $item->mype->img1_path ? asset($item->mype->img1_path) : null,
                // 'img2_name' => $item->mype->img2_name,
                // 'img2_url' => $item->mype->img2_path ? asset($item->mype->img2_path) : null,
                // 'img3_name' => $item->mype->img3_name,
                // 'img3_url' => $item->mype->img3_path ? asset($item->mype->img3_path) : null,

                'typedocument' => $item->person->typedocument->name,
                'documentnumber' => $item->person->documentnumber,
                'lastname' => $item->person->lastname,
                'middlename' => $item->person->middlename,
                'name' => $item->person->name,
                'phone' => $item->person->phone,
                'email' => $item->person->email,
                'birthdate' => $item->person->birthday,
                'sick' => $item->person->sick == 'yes' ? 'SI' : 'NO',
                'user_country' => $item->person->pais->name,
                'user_city' => $item->person->city->name,
                'user_province' => $item->person->province->name,
                'user_district' => $item->person->district->name,
                'address' => $item->person->address,
                'gender' => $item->person->gender->name,

                'POS' => $item->mype->hasPos,
                'yape' => $item->mype->hasYape,
                'virtualStore' => $item->mype->hasVistualStore,
                'delivery' => $item->mype->hasDelivery,
                'electronica' => $item->mype->hasElectronicInvoice,
                'isProduce' => $item->mype->isFormalizedPnte,
                'indecopi' => $item->mype->isIndecopi,
                'participadoFeria' => $item->mype->hasParticipatedFair,
                'nameFair' => $item->mype->nameFair ? $item->mype->nameFair : '-',
                'produceFeria' => $item->mype->hasParticipatedProduce,
                'servicio' => $item->mype->nameService ? $item->mype->nameService : '-',
            ];
        });

        // return $result;
        return Excel::download(new FairParticipantsExport($result), 'participant-export.xlsx');

    }
}
