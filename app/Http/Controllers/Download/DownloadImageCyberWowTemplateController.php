<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\CyberwowOffer;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\PngEncoder;

use Intervention\Image\Geometry\Factories\LineFactory;

class DownloadImageCyberWowTemplateController extends Controller
{
    public function generateOfferTemplate($idOffer)
    {
        $offer = CyberwowOffer::with(['imageFull', 'brand.logo160'])->find($idOffer);

        if (!$offer || !$offer->imageFull || !$offer->brand || !$offer->brand->logo160) {
            return response()->json(['error' => 'Oferta, imagen o logo no encontrado'], 404);
        }

        try {

            // $templatePath = storage_path('app/public/images/cyberwow/template.png');

            // // 🧱 Rutas de imágenes
            // $offerImagePath = $this->getStoragePathFromUrl($offer->imageFull->url);
            // $logoPath = $this->getStoragePathFromUrl($offer->brand->logo160->url);


            $templatePath = public_path('cyberwow/template.png'); // esta está bien
            $offerImagePath = public_path('storage/images/cyberwow/main/' . basename($offer->imageFull->url));
            $logoPath = public_path('storage/images/cyberwow/main/' . basename($offer->brand->logo160->url));

            $missing = [];

            if (!file_exists($templatePath)) {
                $missing[] = 'template';
            }
            if (!file_exists($offerImagePath)) {
                $missing[] = 'offerImage';
            }
            if (!file_exists($logoPath)) {
                $missing[] = 'logo';
            }

            if (!empty($missing)) {
                return response()->json([
                    'error' => 'No se encontró alguna imagen requerida',
                    'faltantes' => $missing,
                    'rutas' => [
                        'templatePath' => $templatePath,
                        'offerImagePath' => $offerImagePath,
                        'logoPath' => $logoPath,
                    ],
                ], 404);
            }

            if (!file_exists($templatePath) || !file_exists($offerImagePath) || !file_exists($logoPath)) {
                return response()->json(['error' => 'No se encontró alguna imagen requerida'], 404);
            }

            // 🧩 Fuentes personalizadas
            $fonts = [
                'regular' => public_path('fonts/Oswald-Regular.ttf'),
                'bold'    => public_path('fonts/Oswald-Bold.ttf'),
            ];

            $manager = new ImageManager(new Driver());
            $template = $manager->read($templatePath);

            // 🖼️ Imagen del producto
            $offerImage = $manager->read($offerImagePath)->resize(500, 500);
            $template->place($offerImage, 'left', 80, 160);

            // 🧩 Logo
            $logo = $manager->read($logoPath)->resize(160, 160);
            $template->place($logo, 'left', 260, -200);

            // 🎨 Colores y posiciones
            $blue = '#0334ff';
            $textX = 700;
            $textY = 650;
            $maxWidth = 500;
            $fontSize = 36;
            $lineHeight = 50;

            // 🟦 Texto "OFERTA"
            $template->text('OFERTA', $textX, $textY - 90, function ($font) use ($fonts, $blue) {
                $font->filename($fonts['bold']);
                $font->size(66);
                $font->color($blue);
                $font->align('left');
                $font->valign('top');
            });

            // 🔹 Título con salto automático
            $lines = $this->wrapTextByWidth($offer->title, $fonts['regular'], $fontSize, $maxWidth);
            foreach ($lines as $i => $line) {
                $y = $textY + ($i * $lineHeight);
                $template->text($line, $textX, $y, function ($font) use ($fonts, $blue, $fontSize) {
                    $font->filename($fonts['regular']);
                    $font->size($fontSize);
                    $font->color($blue);
                    $font->align('left');
                    $font->valign('top');
                });
            }

            $nextY = $textY + (count($lines) * $lineHeight) + 60;

            // 🟩 Si tiene descripción → mostrar "BENEFICIO"
            if (!empty(trim($offer->descripcion ?? ''))) {
                $template->text('BENEFICIO', $textX, $nextY, function ($font) use ($fonts, $blue) {
                    $font->filename($fonts['bold']);
                    $font->size(66);
                    $font->color($blue);
                    $font->align('left');
                    $font->valign('top');
                });

                $descY = $nextY + 90;
                $linesDesc = $this->wrapTextByWidth($offer->descripcion, $fonts['regular'], 34, $maxWidth);
                foreach ($linesDesc as $i => $line) {
                    $y = $descY + ($i * 45);
                    $template->text($line, $textX, $y, function ($font) use ($fonts, $blue) {
                        $font->filename($fonts['regular']);
                        $font->size(34);
                        $font->color($blue);
                        $font->align('left');
                        $font->valign('top');
                    });
                }
            } else {
                // 🟥 Si no hay descripción → mostrar precios
                $precioAnterior = $offer->precioAnterior ?? null;
                $precioOferta   = $offer->precioOferta ?? null;

                if ($precioAnterior && $precioOferta) {

                    $moneda = $offer->moneda ?? 'S/';

                    $template->text('ANTES:', $textX, $nextY, function ($font) use ($fonts, $blue) {
                        $font->filename($fonts['bold']);
                        $font->size(32);
                        $font->color($blue);
                        $font->align('left');
                        $font->valign('top');
                    });

                    // Precio anterior (tachado)
                    $priceX = $textX + 150;
                    $priceText = "{$moneda} " . number_format($precioAnterior, 2);
                    $template->text($priceText, $priceX, $nextY, function ($font) use ($fonts, $blue) {
                        $font->filename($fonts['regular']);
                        $font->size(36);
                        $font->color($blue);
                        $font->align('left');
                        $font->valign('top');
                    });

                    // 🔸 Línea tachando el precio anterior
                    $box = imagettfbbox(28, 5, $fonts['bold'], $priceText);
                    $width = abs($box[4] - $box[0]);
                    $lineY = $nextY + 14;



                    $template->drawLine(function (LineFactory $line) use ($priceX, $lineY, $width, $blue) {
                        $line->from($priceX, $lineY);           // punto inicial
                        $line->to($priceX + $width, $lineY);    // punto final
                        $line->color($blue);                    // color de la línea
                        $line->width(3);                        // grosor en píxeles
                    });


                    // Precio de oferta destacado
                    $ofertaY = $nextY + 80;
                    $template->text("AHORA: {$moneda} " . number_format($precioOferta, 2), $textX, $ofertaY, function ($font) use ($fonts, $blue) {
                        $font->filename($fonts['bold']);
                        $font->size(50);
                        $font->color('#fc2b72');
                        $font->align('left');
                        $font->valign('top');
                    });
                }
            }

            // 📦 Exportar PNG
            $encoded = $template->encode(new PngEncoder());
            $binary = $encoded->toString();

            return response()->streamDownload(fn() => print($binary), "oferta.png", [
                'Content-Type' => 'image/png',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error generando plantilla de oferta: ' . $e->getMessage());
            return response()->json(['error' => 'Error procesando imagen: ' . $e->getMessage()]);
        }
    }


    /**
     * 🔹 Convierte URL de storage a ruta local
     */
    private function getStoragePathFromUrl($url)
    {
        $relative = str_replace(url('/storage') . '/', '', $url);
        return storage_path('app/public/' . $relative);
    }

    /**
     * 🔹 Ajusta texto según ancho máximo real en píxeles
     */
    /**
     * 🔹 Ajusta texto según ancho máximo real en píxeles
     */
    private function wrapTextByWidth($text, $fontFile, $fontSize, $maxWidth)
    {
        // Asegura codificación UTF-8
        $text = mb_convert_encoding($text, 'UTF-8', 'auto');

        $words = preg_split('/\s+/u', $text); // usa unicode-safe split
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = trim($currentLine . ' ' . $word);

            // mide ancho del texto actual
            $box = imagettfbbox($fontSize, 0, $fontFile, $testLine);
            $textWidth = abs($box[4] - $box[0]);

            if ($textWidth > $maxWidth && $currentLine !== '') {
                $lines[] = trim($currentLine);
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }

        if ($currentLine !== '') {
            $lines[] = trim($currentLine);
        }

        return $lines;
    }
}
