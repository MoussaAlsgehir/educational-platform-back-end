<?php

namespace App\Services;

use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificatePdfService
{
    public function generateCertificatePdf(Certificate $certificate): array
    {
        $certificate->load(['student', 'course']);
        $serialNumber = $this->generateSerialNumber($certificate);

        $data = [
            'student_name'  => $certificate->student->name,
            'course_title'  => $certificate->course->title,
            'issued_date'   => $certificate->issued_at->format('Y-m-d'),
            'serial_number' => $serialNumber,
        ];

        $pdf = Pdf::loadView('certificates.certificate-template', $data);
        $pdf->setPaper('A4', 'landscape');

        // تفعيل الخيارات الأساسية للوصول الخارجي
        $pdf->setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
            'chroot' => public_path(),
        ]);

        // تم حل مشكلة الاسم هنا باستخدام الـ serialNumber المضمون وجوده دائماً
        $filename = "certificates/cert_" . $serialNumber . ".pdf";
        Storage::disk('public')->put($filename, $pdf->output());

        return ['certificate_url' => $filename, 'serial_number' => $serialNumber];
    }

    private function generateSerialNumber(Certificate $certificate): string
    {
        return now()->year . "-CERT-" . substr(md5($certificate->id . uniqid()), 0, 8);
    }

    public function deleteCertificatePdf(string $path): bool
    {
        return Storage::disk('public')->exists($path) ? Storage::disk('public')->delete($path) : false;
    }

    public function getCertificateDownloadUrl(string $path): string
    {
        return Storage::disk('public')->url($path);
    }
}
