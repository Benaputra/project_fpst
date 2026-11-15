<?php

namespace App\Services;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeService
{
    /**
     * Hasilkan QR Code offline dalam format data URI base64 SVG
     * agar kompatibel penuh dengan Dompdf (isRemoteEnabled = false) dan browser.
     */
    public function makeSvgDataUri(string $payload): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => true,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
            'svgAddXmlHeader' => true,
        ]);

        return (new QRCode($options))->render($payload);
    }
}
